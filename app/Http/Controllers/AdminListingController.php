<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationSender;
use App\Models\AdminListing;
use App\Models\Business;
use App\Models\BusinessAddress;
use App\Models\BusinessCustomHour;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Media;
use App\Models\NotificationLog;
use App\Models\RefCountry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

// Businesses an admin lists from another directory (already paying there), then invites the owner to claim
class AdminListingController extends Controller
{
    // Default hours for admin-listed businesses: Monday-Saturday 8 AM - 11 PM, Sunday closed (1 = Sunday)
    private const DAY_NAMES = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];

    public function business_create()
    {
        $categories = Category::orderBy('cat_name')->get();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);
        $ref_countries = RefCountry::orderBy('printable_name')->get();
        $sources = AdminListing::whereNotNull('source_name')->distinct()->orderBy('source_name')->pluck('source_name');
        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();

        return view('admin_listings.create', compact('parents', 'children', 'ref_countries', 'sources', 'template'));
    }

    // Live duplicate check while the admin types (website domain and business name against current listings)
    public function business_check(Request $request)
    {
        $domain = $this->normalize_domain((string) $request->business_website);

        return response()->json([
            'website' => $domain ? $this->duplicate_payload($this->find_by_domain($domain)) : null,
            'name'    => trim((string) $request->business_name) !== '' ? $this->duplicate_payload($this->find_by_name($request->business_name)) : null,
            'email'   => $request->filled('business_email') ? Business::where('business_email', trim($request->business_email))->count() : 0,
            'domain'  => $domain,
        ]);
    }

    public function business_store(Request $request)
    {
        $request->validate([
            'business_website' => 'required|string|max:255',
            'business_name'    => 'required|string|max:255',
            'business_email'   => 'required|email|max:50',
            'category_id'      => 'required|integer|exists:categories,category_id',
            'business_desc'    => 'nullable|string|max:5000',
            'logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'street_line_1'    => 'nullable|string|max:50',
            'street_line_2'    => 'nullable|string|max:50',
            'city'             => 'nullable|string|max:50',
            'province_state_territory' => 'nullable|string|max:50',
            'postal_code'      => 'nullable|string|max:10',
            'ref_country_id'   => 'nullable|required_with:street_line_1,street_line_2,city,province_state_territory,postal_code|integer|exists:ref_countries,ref_countries_id',
            'source_name'      => 'nullable|string|max:100',
            'source_url'       => 'nullable|url|max:500',
        ], [
            'business_website.required' => 'Enter the business website.',
            'business_email.max'        => 'The email can be at most 50 characters.',
            'category_id.required'      => 'Choose a category.',
            'logo.max'                  => 'The logo must be 2 MB or smaller.',
            'ref_country_id.required_with' => 'Choose a country for the address.',
        ]);

        $domain = $this->normalize_domain($request->business_website);
        if (!$domain) {
            return back()->withErrors(['business_website' => 'Enter a valid website, for example https://example.com'])->withInput();
        }
        $website = 'https://' . $domain;
        if (strlen($website) > 60) {
            return back()->withErrors(['business_website' => 'The website domain is too long (max 52 characters).'])->withInput();
        }

        $name = trim(preg_replace('/\s+/', ' ', $request->business_name));
        $slug = Str::slug($name);
        if ($slug === '') {
            return back()->withErrors(['business_name' => 'Enter a business name with letters or numbers.'])->withInput();
        }

        // Every listing must be unique by website and by name
        if ($existing = $this->find_by_domain($domain)) {
            return back()->withErrors(['business_website' => 'This website is already listed as "' . $existing->business_name . '".'])
                ->with('duplicate_id', encrypt($existing->business_id))->withInput();
        }
        if ($existing = $this->find_by_name($name)) {
            return back()->withErrors(['business_name' => 'A business with this name is already listed (' . ($existing->business_website ?: 'no website') . ').'])
                ->with('duplicate_id', encrypt($existing->business_id))->withInput();
        }

        $email = strtolower(trim($request->business_email));
        $owner = User::where('email', $email)->first();
        if ($owner && $owner->is_admin) {
            return back()->withErrors(['business_email' => 'This email belongs to an admin account. Use the business\'s own email.'])->withInput();
        }

        $business = DB::transaction(function () use ($request, $owner, $email, $name, $slug, $website) {
            // Placeholder owner account with a random password: the owner sets their own when they claim
            if (!$owner) {
                $owner = new User();
                $owner->email = $email;
                $owner->password = Hash::make(Str::random(40));
                $owner->user_role = 'business_user';
                $owner->is_active = 0;
                $owner->save();
            }

            $business = new Business();
            $business->user_id = $owner->id;
            $business->business_name = $name;
            $business->business_website = $website;
            $business->wwd_url = $slug;
            $business->business_email = $email;
            $business->category_id = $request->category_id;
            $business->business_desc = $request->business_desc;
            $business->is_admin_verified = 1;
            $business->is_admin_posted = 1;
            $business->is_claimed = 0;
            $business->save();

            foreach (self::DAY_NAMES as $day => $day_name) {
                $closed = $day === 1;
                BusinessCustomHour::insert([
                    'business_id' => $business->business_id,
                    'day_of_week' => $day,
                    'name'        => $day_name,
                    'is_closed'   => $closed ? 1 : 0,
                    'start_ts'    => $closed ? null : '08:00:00',
                    'end_ts'      => $closed ? null : '23:00:00',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            if ($request->filled('ref_country_id')) {
                BusinessAddress::create([
                    'business_id'              => $business->business_id,
                    'street_line_1'            => $request->street_line_1,
                    'street_line_2'            => $request->street_line_2,
                    'city'                     => $request->city,
                    'province_state_territory' => $request->province_state_territory,
                    'postal_code'              => $request->postal_code,
                    'ref_country_id'           => $request->ref_country_id,
                    'ref_address_type_id'      => 2,
                ]);
            }

            AdminListing::create([
                'business_id' => $business->business_id,
                'source_name' => $request->filled('source_name') ? trim($request->source_name) : null,
                'source_url'  => $request->source_url,
                'added_by'    => Auth::id(),
            ]);

            return $business;
        });

        // Logo: same 500x500 transparent PNG as Edit business; a failed image doesn't undo the listing
        $logo_note = '';
        if ($request->hasFile('logo')) {
            try {
                $file_name = $business->business_id . '_' . rand(111111, 999999) . '.png';
                $image = (new ImageManager(new Driver()))->read($request->file('logo'))
                    ->scaleDown(500, 500)->pad(500, 500, 'rgba(0,0,0,0)')->toPng();
                Storage::disk('shared')->put('logo/' . $file_name, (string) $image);
                Media::create(['ref_table' => 'businesses', 'ref_id' => $business->business_id, 'file_name' => $file_name,
                    'file_path' => $file_name, 'file_type' => 'logo']);
            } catch (\Throwable $e) {
                $logo_note = ' The logo could not be saved; upload it on Edit business.';
            }
        }

        [$sent, $email_note] = $this->send_invite($business, 'auto', 0);

        return redirect()->route('admin_listings')
            ->with($sent && $logo_note === '' ? 'success' : 'error', '"' . $name . '" is listed. ' . $email_note . $logo_note);
    }

    // Monitoring: the funnel from listed to claimed and paid, per source and per admin, plus every listing
    public function admin_listings(Request $request)
    {
        // Record claims that happened since the last visit (the owner claims on WWD Business)
        AdminListing::whereNull('claimed_at')
            ->whereIn('business_id', Business::where('is_claimed', 1)->select('business_id'))
            ->update(['claimed_at' => now()]);

        $period = in_array($request->period, ['7', '30', '90', 'all']) ? $request->period : '30';
        $since = $period === 'all' ? null : now()->subDays((int) $period)->startOfDay();

        $invite_logs = NotificationLog::where('type', NotificationSender::LISTING_INVITE)
            ->selectRaw("business_id,
                SUM(status = 'sent') as sent,
                SUM(status = 'failed') as failed,
                SUM(click_count > 0) as clicked_emails,
                MAX(sent_at) as last_sent_at,
                MAX(clicked_at) as last_clicked_at")
            ->groupBy('business_id');

        $base = AdminListing::join('businesses', 'businesses.business_id', '=', 'admin_listings.business_id')
            ->leftJoinSub($invite_logs, 'invites', 'invites.business_id', '=', 'admin_listings.business_id')
            ->when($since, fn($q) => $q->where('admin_listings.created_at', '>=', $since));

        // "Claim started" = the owner asked for a claim code (or finished the claim)
        $started = '(businesses.is_claimed = 1 OR businesses.otp_expires_at IS NOT NULL)';

        $funnel = (clone $base)->selectRaw("COUNT(*) as listed,
            SUM(COALESCE(invites.sent, 0) > 0) as emailed,
            SUM(COALESCE(invites.failed, 0) > 0 AND COALESCE(invites.sent, 0) = 0) as email_failed,
            SUM(COALESCE(invites.clicked_emails, 0) > 0) as clicked,
            SUM($started) as started,
            SUM(businesses.is_claimed = 1) as claimed,
            SUM(businesses.business_status = 'P') as premium,
            SUM(businesses.notifications_paused = 1) as unsubscribed,
            AVG(CASE WHEN admin_listings.claimed_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, admin_listings.created_at, admin_listings.claimed_at) END) as avg_hours_to_claim,
            SUM(DATE(admin_listings.created_at) = ?) as today", [now()->toDateString()])->first();

        $by_source = (clone $base)
            ->selectRaw("COALESCE(NULLIF(admin_listings.source_name, ''), 'Not recorded') as source,
                COUNT(*) as listed, SUM(COALESCE(invites.clicked_emails, 0) > 0) as clicked, SUM(businesses.is_claimed = 1) as claimed")
            ->groupBy('source')->orderByDesc('listed')->limit(10)->get();

        $by_admin = (clone $base)->leftJoin('users as admins', 'admins.id', '=', 'admin_listings.added_by')
            ->selectRaw("admin_listings.added_by, CONCAT_WS(' ', admins.first_name, admins.last_name) as admin_name,
                COUNT(*) as listed, SUM(businesses.is_claimed = 1) as claimed")
            ->groupBy('admin_listings.added_by', 'admin_name')->orderByDesc('listed')->limit(10)->get();

        $status = in_array($request->status, ['not_emailed', 'emailed', 'clicked', 'started', 'claimed', 'premium', 'unsubscribed']) ? $request->status : null;

        $listings = (clone $base)
            ->leftJoin('users as admins', 'admins.id', '=', 'admin_listings.added_by')
            ->leftJoin('categories', 'categories.category_id', '=', 'businesses.category_id')
            ->select('admin_listings.*', 'businesses.business_name', 'businesses.business_website', 'businesses.business_email',
                'businesses.is_claimed', 'businesses.otp_expires_at', 'businesses.business_status', 'businesses.notifications_paused',
                'categories.cat_name as category_name', 'invites.sent', 'invites.failed', 'invites.clicked_emails',
                'invites.last_sent_at', 'invites.last_clicked_at')
            ->selectRaw("CONCAT_WS(' ', admins.first_name, admins.last_name) as admin_name")
            ->when($status === 'not_emailed', fn($q) => $q->whereRaw('COALESCE(invites.sent, 0) = 0'))
            ->when($status === 'emailed', fn($q) => $q->whereRaw('COALESCE(invites.sent, 0) > 0'))
            ->when($status === 'clicked', fn($q) => $q->whereRaw('COALESCE(invites.clicked_emails, 0) > 0'))
            ->when($status === 'started', fn($q) => $q->whereRaw($started . ' AND businesses.is_claimed = 0'))
            ->when($status === 'claimed', fn($q) => $q->where('businesses.is_claimed', 1))
            ->when($status === 'premium', fn($q) => $q->where('businesses.business_status', 'P'))
            ->when($status === 'unsubscribed', fn($q) => $q->where('businesses.notifications_paused', 1))
            ->when($request->filled('source'), fn($q) => $q->whereRaw("COALESCE(NULLIF(admin_listings.source_name, ''), 'Not recorded') = ?", [$request->source]))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('businesses.business_name', 'like', $term)
                        ->orWhere('businesses.business_website', 'like', $term)
                        ->orWhere('businesses.business_email', 'like', $term);
                });
            })
            ->orderByDesc('admin_listings.admin_listing_id')
            ->paginate(50)
            ->withQueryString();

        $today = now()->startOfDay();
        foreach ($listings as $listing) {
            $days = Carbon::parse($listing->created_at)->startOfDay()->diffInDays($today);
            $listing->next_step = $listing->is_claimed || $listing->notifications_paused ? null : NotificationSender::listing_next_step($days);
            $listing->next_date = $listing->next_step !== null ? Carbon::parse($listing->created_at)->startOfDay()->addDays($listing->next_step) : null;
        }

        $sources = AdminListing::selectRaw("DISTINCT COALESCE(NULLIF(source_name, ''), 'Not recorded') as source")->orderBy('source')->pluck('source');
        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();
        $has_filters = $request->filled('q') || $request->filled('source') || $status;

        return view('admin_listings.index', compact('funnel', 'by_source', 'by_admin', 'listings', 'period', 'status', 'sources', 'template', 'has_filters'));
    }

    // Manual resend of the claim invite (not tied to a step, so the automatic schedule is unchanged)
    public function admin_listing_resend($business_id)
    {
        $business = Business::findOrFail($this->decrypt_id($business_id));
        if ($business->is_claimed) {
            return back()->with('error', '"' . $business->business_name . '" is already claimed.');
        }
        [$sent, $note] = $this->send_invite($business, 'manual', null);

        return back()->with($sent ? 'success' : 'error', $note);
    }

    // Sends the claim invite; returns [sent?, sentence for the flash message]
    private function send_invite(Business $business, string $trigger, ?int $step): array
    {
        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();
        if (!$template || !$template->is_active) {
            return [false, 'The invite email was not sent: the "Business listed by WWD" template is switched off in Email templates.'];
        }
        if ($business->notifications_paused) {
            return [false, 'The invite email was not sent: this business unsubscribed from WWD emails.'];
        }

        $log = NotificationSender::send($template, $business, User::find($business->user_id), $step, $trigger);

        return $log && $log->status === 'sent'
            ? [true, 'Invite emailed to ' . $business->business_email . '.']
            : [false, 'The invite email failed: ' . ($log->error ?? 'unknown error') . '.'];
    }

    // "https://www.Example.com/about?x" -> "example.com"; null if it isn't a domain
    private function normalize_domain(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'http://' . $url;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', rtrim($host, '.'));

        return preg_match('/^(?=.{4,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host) ? $host : null;
    }

    // Same website, whatever its scheme, www or path in businesses.business_website
    private function find_by_domain(string $domain): ?Business
    {
        return Business::whereRaw("SUBSTRING_INDEX(REPLACE(REPLACE(REPLACE(LOWER(TRIM(business_website)), 'https://', ''), 'http://', ''), 'www.', ''), '/', 1) = ?", [$domain])
            ->first(['business_id', 'business_name', 'business_website', 'is_admin_verified']);
    }

    // Same name: identical slug (the public URL) or identical name ignoring case and spaces
    private function find_by_name(string $name): ?Business
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $slug = Str::slug($name);

        return Business::where(function ($q) use ($name, $slug) {
            $q->whereRaw('LOWER(TRIM(business_name)) = ?', [mb_strtolower($name)]);
            if ($slug !== '') {
                $q->orWhere('wwd_url', $slug);
            }
        })->first(['business_id', 'business_name', 'business_website', 'is_admin_verified']);
    }

    private function duplicate_payload(?Business $business): ?array
    {
        return $business ? [
            'name'    => $business->business_name,
            'website' => $business->business_website,
            'url'     => route('edit_business', encrypt($business->business_id)),
        ] : null;
    }

    private function decrypt_id($id)
    {
        try {
            return decrypt($id);
        } catch (DecryptException $e) {
            abort(404);
        }
    }
}
