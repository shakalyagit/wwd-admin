<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationSender;
use App\Helpers\SimpleXlsx;
use App\Models\AdminListing;
use App\Models\AdminListingDraft;
use App\Models\AdminListingImport;
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
use Illuminate\Support\Facades\Validator;
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
        $phone_codes = $this->phone_codes();

        return view('admin_listings.create', compact('parents', 'children', 'ref_countries', 'sources', 'template', 'phone_codes'));
    }

    // "+1" => "United States (+1)", United States first; from the rinvex countries list
    private function phone_codes(): array
    {
        $codes = [];
        foreach (countries() as $country) {
            if (is_array($country) && !empty($country['calling_code'])) {
                $code = '+' . explode(' ', (string) $country['calling_code'])[0];
                $codes[$code] = $codes[$code] ?? $country['name'] . ' (' . $code . ')';
            }
        }
        asort($codes);
        return ['+1' => 'United States / Canada (+1)'] + $codes;
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
        $request->validate($this->listing_rules() + [
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], $this->listing_messages());

        [$errors, $data] = $this->check_listing($request->all());
        if ($errors) {
            return back()->withErrors($errors)->with('duplicate_id', $data['duplicate_id'] ?? null)->withInput();
        }
        if ($request->hasFile('logo') && ($logo_error = $this->logo_too_small($request->file('logo')->getRealPath()))) {
            return back()->withErrors(['logo' => $logo_error])->withInput();
        }

        $data['added_by'] = Auth::id();
        [$business, $sent, $note] = $this->create_listing($data, $request->file('logo'));

        return redirect()->route('admin_listings')->with($sent ? 'success' : 'error', '"' . $business->business_name . '" is listed. ' . $note);
    }

    // Field rules shared by Add business and the Excel draft review form
    private function listing_rules(): array
    {
        return [
            'business_website' => 'required|string|max:255',
            'business_name'    => 'required|string|max:255',
            'business_email'   => 'required|email|max:50',
            'category_id'      => 'required|integer|exists:categories,category_id',
            'country_code'     => ['nullable', 'string', 'max:8', 'regex:/^\+[0-9]{1,4}$/'],
            'business_phone'   => ['nullable', 'string', 'max:45', 'regex:/^[0-9 ()+.\-]{5,45}$/'],
            'business_desc'    => 'nullable|string|max:5000',
            'street_line_1'    => 'nullable|string|max:50',
            'street_line_2'    => 'nullable|string|max:50',
            'city'             => 'nullable|string|max:50',
            'province_state_territory' => 'nullable|string|max:50',
            'postal_code'      => 'nullable|string|max:10',
            'ref_country_id'   => 'nullable|required_with:street_line_1,street_line_2,city,province_state_territory,postal_code|integer|exists:ref_countries,ref_countries_id',
            'source_name'      => 'nullable|string|max:100',
            'source_url'       => 'nullable|url|max:500',
        ];
    }

    private function listing_messages(): array
    {
        return [
            'business_website.required'    => 'Enter the business website.',
            'business_email.max'           => 'The email can be at most 50 characters.',
            'category_id.required'         => 'Choose a category.',
            'business_phone.regex'         => 'Use digits, spaces, brackets, dots or dashes for the phone number.',
            'country_code.regex'           => 'Choose the phone country code.',
            'logo.max'                     => 'The logo must be 5 MB or smaller.',
            'ref_country_id.required_with' => 'Choose a country for the address.',
        ];
    }

    /**
     * Checks that need the database: website domain, unique website and name, owner email.
     * Returns [errors keyed by field, cleaned data].
     */
    private function check_listing(array $input): array
    {
        $errors = [];
        $data = [
            'business_name'  => trim(preg_replace('/\s+/', ' ', (string) ($input['business_name'] ?? ''))),
            'business_email' => strtolower(trim((string) ($input['business_email'] ?? ''))),
            'category_id'    => $input['category_id'] ?? null,
            'country_code'   => trim((string) ($input['country_code'] ?? '')) ?: null,
            'business_phone' => trim((string) ($input['business_phone'] ?? '')) ?: null,
            'business_desc'  => trim((string) ($input['business_desc'] ?? '')) ?: null,
            'source_name'    => trim((string) ($input['source_name'] ?? '')) ?: null,
            'source_url'     => trim((string) ($input['source_url'] ?? '')) ?: null,
        ];
        foreach (['street_line_1', 'street_line_2', 'city', 'province_state_territory', 'postal_code', 'ref_country_id'] as $field) {
            $data[$field] = trim((string) ($input[$field] ?? '')) ?: null;
        }
        if ($data['business_phone'] && !$data['country_code']) {
            $data['country_code'] = '+1';
        }

        $domain = $this->normalize_domain((string) ($input['business_website'] ?? ''));
        if (!$domain) {
            $errors['business_website'] = 'Enter a valid website, for example https://example.com';
        } elseif (strlen('https://' . $domain) > 60) {
            $errors['business_website'] = 'The website domain is too long (max 52 characters).';
        } elseif ($existing = $this->find_by_domain($domain)) {
            $errors['business_website'] = 'This website is already listed as "' . $existing->business_name . '".';
            $data['duplicate_id'] = encrypt($existing->business_id);
        }
        $data['business_website'] = $domain ? 'https://' . $domain : null;
        $data['domain'] = $domain;

        $data['wwd_url'] = Str::slug($data['business_name']);
        if ($data['wwd_url'] === '') {
            $errors['business_name'] = 'Enter a business name with letters or numbers.';
        } elseif ($existing = $this->find_by_name($data['business_name'])) {
            $errors['business_name'] = 'A business with this name is already listed (' . ($existing->business_website ?: 'no website') . ').';
            $data['duplicate_id'] = $data['duplicate_id'] ?? encrypt($existing->business_id);
        }

        if ($data['business_email'] !== '' && User::where('email', $data['business_email'])->where('is_admin', 1)->exists()) {
            $errors['business_email'] = 'This email belongs to an admin account. Use the business\'s own email.';
        }

        return [$errors, $data];
    }

    // Same minimum as WWD Business: a tiny image looks blurry on the listing
    private function logo_too_small(string $path): ?string
    {
        $size = @getimagesize($path);
        if (!$size) {
            return 'The logo must be a JPG, PNG or WebP image.';
        }
        return max($size[0], $size[1]) < 200
            ? 'The logo is only ' . $size[0] . ' × ' . $size[1] . ' px. Use an image at least 200 px wide; 500 × 500 px or larger looks best.'
            : null;
    }

    /**
     * Creates the live listing (Add business and approved Excel drafts): owner account, business, hours, address,
     * admin_listings row, logo, then the claim invite. $data comes from check_listing(); $logo is an uploaded file or a
     * file path. Returns [Business, invite sent?, sentence for the flash message].
     */
    private function create_listing(array $data, $logo = null): array
    {
        $business = DB::transaction(function () use ($data) {
            // Placeholder owner account with a random password: the owner sets their own when they claim
            $owner = User::where('email', $data['business_email'])->first();
            if (!$owner) {
                $owner = new User();
                $owner->email = $data['business_email'];
                $owner->password = Hash::make(Str::random(40));
                $owner->user_role = 'business_user';
                $owner->is_active = 0;
                $owner->save();
            }

            $business = new Business();
            $business->user_id = $owner->id;
            $business->business_name = $data['business_name'];
            $business->business_website = $data['business_website'];
            $business->wwd_url = $data['wwd_url'];
            $business->business_email = $data['business_email'];
            $business->category_id = $data['category_id'];
            $business->business_desc = $data['business_desc'];
            $business->country_code = $data['business_phone'] ? $data['country_code'] : null;
            $business->business_phone = $data['business_phone'];
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

            if (!empty($data['ref_country_id'])) {
                BusinessAddress::create([
                    'business_id'              => $business->business_id,
                    'street_line_1'            => $data['street_line_1'],
                    'street_line_2'            => $data['street_line_2'],
                    'city'                     => $data['city'],
                    'province_state_territory' => $data['province_state_territory'],
                    'postal_code'              => $data['postal_code'],
                    'ref_country_id'           => $data['ref_country_id'],
                    'ref_address_type_id'      => 2,
                ]);
            }

            AdminListing::create([
                'business_id' => $business->business_id,
                'source_name' => $data['source_name'],
                'source_url'  => $data['source_url'],
                'added_by'    => $data['added_by'] ?? null,
            ]);

            return $business;
        });

        // Logo: same 500x500 PNG as WWD Business; a failed image doesn't undo the listing
        $logo_note = '';
        if ($logo) {
            try {
                $file_name = $business->business_id . '_' . rand(111111, 999999) . '.png';
                $image = (new ImageManager(new Driver()))->read($logo)
                    ->scaleDown(500, 500)->pad(500, 500, 'rgba(0,0,0,0)')->toPng();
                Storage::disk('shared')->put('logo/' . $file_name, (string) $image);
                Media::create(['ref_table' => 'businesses', 'ref_id' => $business->business_id, 'file_name' => $file_name,
                    'file_path' => $file_name, 'file_type' => 'logo']);
            } catch (\Throwable $e) {
                $logo_note = ' The logo could not be saved; upload it on Edit business.';
            }
        }

        [$sent, $email_note] = $this->send_invite($business, 'auto', 0);

        return [$business, $sent && $logo_note === '', $email_note . $logo_note];
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

    /* ---------- Excel import: sample, upload, drafts, review, approve ---------- */

    // Excel columns: field => [header in the sample, other accepted headers (lowercase, letters/digits only)]
    private const IMPORT_COLUMNS = [
        'business_name'            => ['Business name *', ['businessname', 'name', 'company', 'companyname']],
        'business_website'         => ['Website *', ['website', 'websiteurl', 'url', 'site', 'web']],
        'business_email'           => ['Email *', ['email', 'emailaddress', 'businessemail']],
        'category_text'            => ['Category *', ['category', 'businesscategory']],
        'country_code'             => ['Phone country code', ['phonecountrycode', 'countrycode', 'callingcode', 'phonecode']],
        'business_phone'           => ['Phone', ['phone', 'phonenumber', 'businessphone', 'telephone', 'tel']],
        'business_desc'            => ['Description', ['description', 'descriptionforwwdlisting', 'about', 'businessdescription']],
        'street_line_1'            => ['Address line 1', ['addressline1', 'street', 'address', 'streetaddress', 'address1']],
        'street_line_2'            => ['Address line 2', ['addressline2', 'address2', 'suite', 'unit']],
        'city'                     => ['City', ['city', 'town']],
        'province_state_territory' => ['State', ['state', 'province', 'stateprovince', 'region']],
        'postal_code'              => ['ZIP', ['zip', 'zipcode', 'postalcode', 'postcode']],
        'country_text'             => ['Country', ['country']],
        'source_name'              => ['Source site', ['sourcesite', 'source', 'foundon']],
        'source_url'               => ['Source page URL', ['sourcepageurl', 'sourcepage', 'sourceurl', 'sourcelink']],
    ];
    private const IMPORT_MAX_ROWS = 500;

    public function listing_import(Request $request)
    {
        $status = array_key_exists($request->status, AdminListingDraft::STATUSES) ? $request->status : 'uploaded';
        $counts = AdminListingDraft::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $drafts = AdminListingDraft::leftJoin('categories', 'categories.category_id', '=', 'admin_listing_drafts.category_id')
            ->leftJoin('admin_listing_imports', 'admin_listing_imports.admin_listing_import_id', '=', 'admin_listing_drafts.admin_listing_import_id')
            ->select('admin_listing_drafts.*', 'categories.cat_name as category_name', 'admin_listing_imports.file_name')
            ->where('admin_listing_drafts.status', $status)
            ->when($request->filled('import'), fn($q) => $q->where('admin_listing_drafts.admin_listing_import_id', (int) $request->import))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('admin_listing_drafts.business_name', 'like', $term)
                        ->orWhere('admin_listing_drafts.business_website', 'like', $term)
                        ->orWhere('admin_listing_drafts.business_email', 'like', $term);
                });
            })
            ->orderByDesc('admin_listing_drafts.admin_listing_import_id')
            ->orderBy('admin_listing_drafts.row_number')
            ->paginate(50)
            ->withQueryString();

        $imports = AdminListingImport::leftJoin('users', 'users.id', '=', 'admin_listing_imports.uploaded_by')
            ->select('admin_listing_imports.*')
            ->selectRaw("CONCAT_WS(' ', users.first_name, users.last_name) as uploader")
            ->orderByDesc('admin_listing_import_id')
            ->limit(10)
            ->get();
        $last_import = session('import_id') ? AdminListingImport::find(session('import_id')) : null;
        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();

        return view('admin_listings.import', compact('drafts', 'counts', 'status', 'imports', 'last_import', 'template'));
    }

    // Sample file, built fresh so the Categories sheet always matches the current WWD categories
    public function listing_import_sample()
    {
        $categories = $this->category_names();
        $headers = array_column(self::IMPORT_COLUMNS, 0);
        $example = ['Sunrise Bakery', 'https://sunrisebakery.com', 'info@sunrisebakery.com', $categories[0] ?? '', '+1', '(512) 555-0142',
            'Family bakery baking bread, cakes and pastries fresh every morning, with custom cakes for birthdays and weddings.',
            '120 Main St', 'Suite 4', 'Austin', 'TX', '78701', 'United States', 'BBB', 'https://www.bbb.org/us/tx/austin/profile/bakery/sunrise-bakery'];
        $category_column = SimpleXlsx::column_letter(array_search('category_text', array_keys(self::IMPORT_COLUMNS)));

        $help = [
            ['Column', 'Required?', 'How to fill it'],
            ['Business name', 'Yes', 'Must not already be listed on WWD (rows that are get skipped).'],
            ['Website', 'Yes', 'The business\'s own site. Only the domain is kept, e.g. https://example.com. Must not already be listed on WWD.'],
            ['Email', 'Yes', 'The business email. The claim invite is sent here when you approve the row.'],
            ['Category', 'Yes', 'Pick from the list (it comes from the Categories sheet). Anything else is marked Needs fix.'],
            ['Phone country code', 'No', 'e.g. +1 for the USA. Leave empty for +1.'],
            ['Phone', 'No', 'Digits, spaces, brackets and dashes, e.g. (512) 555-0142.'],
            ['Description', 'No', 'Shown on the listing. Write it in your own words.'],
            ['Address line 1 – ZIP', 'No', 'If you fill any address field, fill Country too.'],
            ['Country', 'No', 'Country name, e.g. United States.'],
            ['Source site / Source page URL', 'No', 'Where you found the business. Admin reference only, never shown publicly.'],
            ['', '', ''],
            ['Rules', '', 'One business per row, up to ' . self::IMPORT_MAX_ROWS . ' rows. Keep the header row. Delete the example row before uploading.'],
            ['', '', 'Opening hours are set automatically (Mon–Sat 8 AM – 11 PM, Sunday closed). Logos are added on the review page.'],
        ];

        $path = tempnam(sys_get_temp_dir(), 'wwd') . '.xlsx';
        SimpleXlsx::write($path, [
            'Businesses' => [
                'rows'   => [$headers, $example],
                'widths' => [28, 30, 30, 28, 12, 18, 60, 28, 16, 16, 8, 10, 16, 14, 40],
                'wrap'   => [6],
                'note_rows' => [1],
                'lists'  => [['range' => $category_column . '2:' . $category_column . (self::IMPORT_MAX_ROWS + 1),
                    'source' => 'Categories!$A$2:$A$' . (count($categories) + 1), 'error' => 'This isn\'t a WWD category. The row will be marked Needs fix.']],
            ],
            'Categories' => ['rows' => array_merge([['Category']], array_map(fn($name) => [$name], $categories)), 'widths' => [48]],
            'How to fill' => ['rows' => $help, 'widths' => [30, 12, 100], 'wrap' => [2]],
        ]);

        return response()->download($path, 'wwd-business-import-sample.xlsx')->deleteFileAfterSend(true);
    }

    public function listing_import_upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120|mimes:xlsx'], [
            'file.required' => 'Choose the filled Excel file.',
            'file.mimes'    => 'Upload an Excel .xlsx file (download the sample to start from).',
            'file.max'      => 'The file must be 5 MB or smaller.',
        ]);

        $rows = SimpleXlsx::read($request->file('file')->getRealPath(), 'Businesses', self::IMPORT_MAX_ROWS + 1);
        if (!$rows) {
            return back()->with('error', 'This file could not be read. Save it as an Excel workbook (.xlsx) and try again.');
        }

        // Header row: map each known column to its index
        $header = array_shift($rows);
        $map = [];
        foreach ($header as $index => $title) {
            if ($index === '_row') {
                continue;
            }
            $key = preg_replace('/[^a-z0-9]/', '', strtolower((string) $title));
            foreach (self::IMPORT_COLUMNS as $field => [$sample, $aliases]) {
                if (!isset($map[$field]) && ($key === preg_replace('/[^a-z0-9]/', '', strtolower($sample)) || in_array($key, $aliases, true))) {
                    $map[$field] = $index;
                    break;
                }
            }
        }
        $missing = array_diff(['business_name', 'business_website', 'business_email', 'category_text'], array_keys($map));
        if ($missing) {
            $labels = array_map(fn($f) => rtrim(self::IMPORT_COLUMNS[$f][0], ' *'), $missing);
            return back()->with('error', 'The file is missing these columns: ' . implode(', ', $labels) . '. Start from the sample file.');
        }
        if (count($rows) > self::IMPORT_MAX_ROWS) {
            return back()->with('error', 'The file has more than ' . self::IMPORT_MAX_ROWS . ' businesses. Split it into smaller files.');
        }
        if (!$rows) {
            return back()->with('error', 'The file has no businesses under the header row.');
        }

        $categories = $this->category_lookup();
        $countries = $this->country_lookup();
        $seen = [];
        $skipped = [];
        $drafts = [];

        foreach ($rows as $row) {
            $value = fn($field) => isset($map[$field]) ? trim((string) ($row[$map[$field]] ?? '')) : '';
            // The sample's example row is ignored if it was left in
            if (strcasecmp($value('business_website'), 'https://sunrisebakery.com') === 0) {
                continue;
            }

            $input = [];
            foreach (array_keys(self::IMPORT_COLUMNS) as $field) {
                $input[$field] = $value($field);
            }
            $input['category_id'] = $categories[mb_strtolower($input['category_text'])] ?? null;
            $input['ref_country_id'] = $countries[mb_strtolower($input['country_text'])] ?? null;
            $code = preg_replace('/[^0-9]/', '', $input['country_code']);
            $input['country_code'] = $code !== '' ? '+' . $code : ($input['business_phone'] !== '' ? '+1' : '');

            [$problems, $data, $duplicate] = $this->draft_check($input);

            // Already on WWD or repeated in this file: skipped, only reported
            $domain_key = $data['domain'] ? 'd:' . $data['domain'] : null;
            $name_key = $data['wwd_url'] !== '' ? 'n:' . $data['wwd_url'] : null;
            if (!$duplicate && (($domain_key && isset($seen[$domain_key])) || ($name_key && isset($seen[$name_key])))) {
                $duplicate = 'Same business as row ' . ($seen[$domain_key] ?? $seen[$name_key]) . ' in this file';
            }
            if (!$duplicate) {
                $duplicate = $this->draft_duplicate($data);
            }
            if ($duplicate) {
                $skipped[] = ['row' => $row['_row'], 'name' => $input['business_name'] ?: '(no name)', 'reason' => $duplicate];
                continue;
            }
            foreach (array_filter([$domain_key, $name_key]) as $key) {
                $seen[$key] = $row['_row'];
            }

            $drafts[] = $this->draft_attributes($input, $data) + [
                'row_number' => $row['_row'],
                'status'     => $problems ? 'needs_fix' : 'uploaded',
                'problems'   => $problems ?: null,
            ];
        }

        $import = DB::transaction(function () use ($request, $rows, $drafts, $skipped) {
            $import = AdminListingImport::create([
                'file_name'       => Str::limit($request->file('file')->getClientOriginalName(), 250, ''),
                'uploaded_by'     => Auth::id(),
                'total_rows'      => count($rows),
                'uploaded_rows'   => count(array_filter($drafts, fn($d) => $d['status'] === 'uploaded')),
                'needs_fix_rows'  => count(array_filter($drafts, fn($d) => $d['status'] === 'needs_fix')),
                'skipped_rows'    => count($skipped),
                'skipped_details' => $skipped ?: null,
            ]);
            foreach ($drafts as $draft) {
                AdminListingDraft::create($draft + ['admin_listing_import_id' => $import->admin_listing_import_id]);
            }
            return $import;
        });

        $message = count($drafts) . ' of ' . count($rows) . ' ' . Str::plural('row', count($rows)) . ' imported: '
            . $import->uploaded_rows . ' ready to review, ' . $import->needs_fix_rows . ' need fixing'
            . ($import->skipped_rows ? ', ' . $import->skipped_rows . ' skipped (already on WWD or repeated).' : '.');

        return redirect()->route('listing_import', ['status' => $import->needs_fix_rows && !$import->uploaded_rows ? 'needs_fix' : 'uploaded', 'import' => $import->admin_listing_import_id])
            ->with('success', $message)->with('import_id', $import->admin_listing_import_id);
    }

    public function listing_draft($draft_id)
    {
        $draft = AdminListingDraft::findOrFail($this->decrypt_id($draft_id));
        $categories = Category::orderBy('cat_name')->get();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);
        $ref_countries = RefCountry::orderBy('printable_name')->get();
        $sources = AdminListing::whereNotNull('source_name')->distinct()->orderBy('source_name')->pluck('source_name');
        $phone_codes = $this->phone_codes();
        $import = AdminListingImport::find($draft->admin_listing_import_id);
        $template = EmailTemplate::where('template_key', NotificationSender::LISTING_INVITE)->first();
        $business = $draft->business_id ? Business::find($draft->business_id, ['business_id', 'business_name', 'wwd_url']) : null;

        // Previous / next draft in the same tab, for reviewing one after another
        $siblings = AdminListingDraft::where('status', $draft->status)->orderByDesc('admin_listing_import_id')->orderBy('row_number')
            ->pluck('admin_listing_draft_id')->values();
        $position = $siblings->search($draft->admin_listing_draft_id);
        $next_id = $position !== false ? $siblings->get($position + 1) : null;

        return view('admin_listings.draft', compact('draft', 'parents', 'children', 'ref_countries', 'sources', 'phone_codes', 'import', 'template', 'business', 'next_id', 'position', 'siblings'));
    }

    // Draft logo preview (private file, admins only)
    public function listing_draft_logo($draft_id)
    {
        $draft = AdminListingDraft::findOrFail($this->decrypt_id($draft_id));
        abort_unless($draft->logo_file && Storage::disk('local')->exists($draft->logo_file), 404);

        return response()->file(Storage::disk('local')->path($draft->logo_file), ['Cache-Control' => 'private, max-age=60']);
    }

    // Save the review form; "approve" also publishes the business and emails the owner
    public function listing_draft_save(Request $request, $draft_id)
    {
        $draft = AdminListingDraft::findOrFail($this->decrypt_id($draft_id));
        if (in_array($draft->status, ['approved'])) {
            return back()->with('error', 'This business was already approved.');
        }

        $request->validate($this->listing_rules() + [
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], $this->listing_messages());
        if ($request->hasFile('logo') && ($logo_error = $this->logo_too_small($request->file('logo')->getRealPath()))) {
            return back()->withErrors(['logo' => $logo_error])->withInput();
        }

        // Keep the edits and the logo even if approval fails below
        $draft->fill($request->only(AdminListingDraft::FIELDS));
        if ($request->filled('category_id')) {
            $draft->category_text = null;
        }
        if ($request->hasFile('logo')) {
            $draft->logo_file = $this->store_draft_logo($draft, $request->file('logo'));
        }

        [$errors, $data] = $this->check_listing($request->all());
        $draft->problems = $errors ? array_values($errors) : null;
        if ($draft->status !== 'skipped') {
            $draft->status = $errors ? 'needs_fix' : 'uploaded';
        }
        $draft->save();

        if ($request->input('action') !== 'approve') {
            return redirect()->route('listing_draft', encrypt($draft->admin_listing_draft_id))
                ->with($errors ? 'error' : 'success', $errors ? 'Saved. Fix the problems shown before approving.' : 'Saved. Ready to approve.');
        }
        if ($errors) {
            return back()->withErrors($errors)->with('duplicate_id', $data['duplicate_id'] ?? null)->withInput();
        }

        [$sent, $note] = $this->approve_draft($draft, $data);
        $next = $request->input('next') ? AdminListingDraft::where('admin_listing_draft_id', $this->decrypt_id($request->input('next')))->where('status', '!=', 'approved')->first() : null;

        return ($next ? redirect()->route('listing_draft', encrypt($next->admin_listing_draft_id)) : redirect()->route('listing_import', ['status' => 'uploaded']))
            ->with($sent ? 'success' : 'error', '"' . $draft->business_name . '" is live on WWD. ' . $note);
    }

    public function listing_draft_skip(Request $request, $draft_id)
    {
        $draft = AdminListingDraft::findOrFail($this->decrypt_id($draft_id));
        if ($draft->status === 'approved') {
            return back()->with('error', 'This business was already approved.');
        }
        $restore = $request->input('restore') === '1';
        $draft->status = $restore ? ($draft->problems ? 'needs_fix' : 'uploaded') : 'skipped';
        $draft->skip_reason = $restore ? null : Str::limit((string) $request->input('reason'), 250, '');
        $draft->save();

        return redirect()->route('listing_import', ['status' => $restore ? $draft->status : 'skipped'])
            ->with('success', '"' . $draft->business_name . '" ' . ($restore ? 'moved back to ' . AdminListingDraft::STATUSES[$draft->status] : 'skipped') . '.');
    }

    // Approve several drafts at once (only rows without problems)
    public function listing_drafts_approve(Request $request)
    {
        $request->validate(['drafts' => 'required|array|max:100', 'drafts.*' => 'integer'], ['drafts.required' => 'Tick the businesses to approve.']);

        $approved = 0;
        $failed = [];
        $emailed = 0;
        foreach (AdminListingDraft::whereIn('admin_listing_draft_id', $request->drafts)->where('status', 'uploaded')->get() as $draft) {
            [$errors, $data] = $this->check_listing($draft->only(AdminListingDraft::FIELDS));
            if ($errors) {
                $draft->update(['status' => 'needs_fix', 'problems' => array_values($errors)]);
                $failed[] = $draft->business_name;
                continue;
            }
            [$sent] = $this->approve_draft($draft, $data);
            $approved++;
            $emailed += $sent ? 1 : 0;
        }

        $message = $approved . ' ' . Str::plural('business', $approved) . ' approved and live; ' . $emailed . ' invite ' . Str::plural('email', $emailed) . ' sent.';
        if ($failed) {
            $message .= ' Moved to Needs fix: ' . implode(', ', array_slice($failed, 0, 5)) . (count($failed) > 5 ? ' and ' . (count($failed) - 5) . ' more' : '') . '.';
        }

        return back()->with($failed || $emailed < $approved ? 'error' : 'success', $message);
    }

    private function approve_draft(AdminListingDraft $draft, array $data): array
    {
        $data['added_by'] = Auth::id();
        $logo = $draft->logo_file && Storage::disk('local')->exists($draft->logo_file) ? Storage::disk('local')->path($draft->logo_file) : null;

        [$business, $sent, $note] = $this->create_listing($data, $logo);

        if ($logo) {
            Storage::disk('local')->delete($draft->logo_file);
        }
        $draft->update([
            'status'      => 'approved',
            'problems'    => null,
            'business_id' => $business->business_id,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'logo_file'   => null,
        ]);

        return [$sent, $note];
    }

    // Problems for an imported row: field rules + database checks. Returns [problems, cleaned data, duplicate reason].
    private function draft_check(array $input): array
    {
        $validator = Validator::make($input, $this->listing_rules(), $this->listing_messages());
        $problems = $validator->fails() ? $validator->errors()->all() : [];
        if ($input['category_text'] !== '' && !$input['category_id']) {
            $problems = array_values(array_filter($problems, fn($p) => !str_contains($p, 'category')));
            $problems[] = 'Unknown category "' . $input['category_text'] . '". Choose a WWD category.';
        }
        if ($input['country_text'] !== '' && !$input['ref_country_id']) {
            $problems = array_values(array_filter($problems, fn($p) => !str_contains($p, 'country for the address')));
            $problems[] = 'Unknown country "' . $input['country_text'] . '".';
        }

        [$errors, $data] = $this->check_listing($input);
        $duplicate = null;
        foreach (['business_website', 'business_name'] as $field) {
            if (isset($errors[$field]) && (str_contains($errors[$field], 'already listed'))) {
                $duplicate = $duplicate ?? $errors[$field];
                unset($errors[$field]);
            }
        }
        $problems = array_values(array_unique(array_merge($problems, array_values($errors))));

        return [$problems, $data, $duplicate];
    }

    // Also skip rows that match a draft still waiting from an earlier upload
    private function draft_duplicate(array $data): ?string
    {
        $match = AdminListingDraft::whereIn('status', ['uploaded', 'needs_fix'])
            ->where(function ($q) use ($data) {
                if ($data['business_website']) {
                    $q->orWhere('business_website', $data['business_website']);
                }
                if ($data['business_name'] !== '') {
                    $q->orWhereRaw('LOWER(business_name) = ?', [mb_strtolower($data['business_name'])]);
                }
            })
            ->first(['admin_listing_import_id', 'row_number']);

        return $match ? 'Already waiting for review from an earlier upload (row ' . $match->row_number . ')' : null;
    }

    private function draft_attributes(array $input, array $data): array
    {
        return [
            'business_name'            => Str::limit($data['business_name'], 250, '') ?: null,
            'business_website'         => $data['business_website'] ?? (Str::limit($input['business_website'], 250, '') ?: null),
            'business_email'           => Str::limit($data['business_email'], 250, '') ?: null,
            'category_id'              => $input['category_id'],
            'category_text'            => $input['category_id'] ? null : (Str::limit($input['category_text'], 250, '') ?: null),
            'country_code'             => Str::limit($input['country_code'], 8, '') ?: null,
            'business_phone'           => Str::limit($input['business_phone'], 45, '') ?: null,
            'business_desc'            => $data['business_desc'],
            'street_line_1'            => Str::limit($input['street_line_1'], 50, '') ?: null,
            'street_line_2'            => Str::limit($input['street_line_2'], 50, '') ?: null,
            'city'                     => Str::limit($input['city'], 50, '') ?: null,
            'province_state_territory' => Str::limit($input['province_state_territory'], 50, '') ?: null,
            'postal_code'              => Str::limit($input['postal_code'], 10, '') ?: null,
            'ref_country_id'           => $input['ref_country_id'],
            'country_text'             => Str::limit($input['country_text'], 100, '') ?: null,
            'source_name'              => Str::limit($input['source_name'], 100, '') ?: null,
            'source_url'               => Str::limit($input['source_url'], 500, '') ?: null,
        ];
    }

    private function store_draft_logo(AdminListingDraft $draft, $file): string
    {
        $path = 'listing-drafts/' . $draft->admin_listing_draft_id . '_' . Str::random(8) . '.png';
        $image = (new ImageManager(new Driver()))->read($file)->scaleDown(500, 500)->pad(500, 500, 'rgba(0,0,0,0)')->toPng();
        Storage::disk('local')->put($path, (string) $image);
        if ($draft->logo_file && $draft->logo_file !== $path) {
            Storage::disk('local')->delete($draft->logo_file);
        }
        return $path;
    }

    // Category names for the sample's drop-down: "Parent" and "Parent > Child"
    private function category_names(): array
    {
        $categories = Category::orderBy('cat_name')->get(['category_id', 'cat_name', 'parent_cat_id']);
        $parents = $categories->where('parent_cat_id', 0)->keyBy('category_id');
        $names = [];
        foreach ($parents as $parent) {
            $names[] = trim($parent->cat_name);
            foreach ($categories->where('parent_cat_id', $parent->category_id) as $child) {
                $names[] = trim($parent->cat_name) . ' > ' . trim($child->cat_name);
            }
        }
        return $names;
    }

    // lowercase name -> category_id; accepts "Child", "Parent > Child" and "Parent » Child"
    private function category_lookup(): array
    {
        $categories = Category::get(['category_id', 'cat_name', 'parent_cat_id']);
        $by_id = $categories->keyBy('category_id');
        $lookup = [];
        foreach ($categories as $category) {
            $name = mb_strtolower(trim($category->cat_name));
            $parent = $by_id->get($category->parent_cat_id);
            if ($parent) {
                $parent_name = mb_strtolower(trim($parent->cat_name));
                $lookup[$parent_name . ' > ' . $name] = $category->category_id;
                $lookup[$parent_name . ' » ' . $name] = $category->category_id;
            }
            $lookup[$name] = $lookup[$name] ?? $category->category_id;
        }
        return $lookup;
    }

    // lowercase country name -> ref_countries_id, with common US spellings
    private function country_lookup(): array
    {
        $lookup = RefCountry::get()->mapWithKeys(fn($c) => [mb_strtolower(trim((string) $c->printable_name)) => $c->ref_countries_id])->all();
        $us = $lookup['united states'] ?? null;
        foreach (['usa', 'us', 'u.s.', 'u.s.a.', 'united states of america', 'america'] as $alias) {
            if ($us && !isset($lookup[$alias])) {
                $lookup[$alias] = $us;
            }
        }
        return $lookup;
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
