<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessAddress;
use App\Models\BusinessCustomHour;
use App\Models\Category;
use App\Models\OldBusiness;
use App\Models\RefCountry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class BusinessController extends Controller
{
    public function business_list()
    {
        $business = Business::leftjoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->select('businesses.*', 'categories.cat_name as category_name')->orderBy('businesses.business_id', 'desc')->paginate(50);
        $categories = Category::get();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);
        return view('business.index', compact('business', 'categories', 'parents', 'children'));
    }

    public function edit_business($id)
    {
        $decrypt_id = decrypt($id);
        $edit_business = Business::leftjoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->leftjoin('media', 'businesses.business_id', '=', 'media.ref_id')
            ->select('businesses.*', 'media.file_path',  'categories.cat_name as category_name')->find($decrypt_id);
        // dd($edit_business);
        $categories = Category::get();
        $countries = countries();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);
        $business_address = BusinessAddress::where('business_id', $decrypt_id)->first();
        $ref_countries = RefCountry::get();

        $hours = BusinessCustomHour::where('business_id', $edit_business->business_id)->get()->keyBy('day_of_week');
        $has_hours = BusinessCustomHour::where('business_id', $edit_business->business_id)->exists();

        $times = [];
        $start = strtotime('00:00');
        $end   = strtotime('23:30');

        while ($start <= $end) {
            $times[] = date('h:i A', $start);
            $start = strtotime('+30 minutes', $start);
        }

        $business = Business::find($decrypt_id);
        $edit_user = User::where('id', $business->user_id)->first();
        return view('business.edit', compact('edit_business', 'edit_user', 'categories', 'parents', 'children', 'countries', 'business_address', 'ref_countries', 'hours', 'has_hours', 'times'));
    }

    public function edit_business_action(Request $request)
    {
        $request->validate([
            'facebook_url' => [
                'nullable',
                'regex:/^(https?:\/\/)?(www\.)?(facebook\.com|fb\.com)\/[A-Za-z0-9\.\/_\-?=]+$/'
            ],
            'twitter_url' => [
                'nullable',
                'regex:/^(https?:\/\/)?(www\.)?x\.com\/[A-Za-z0-9_]+$/'
            ],
            'instragram_url' => [
                'nullable',
                'regex:/^(https?:\/\/)?(www\.)?instagram\.com\/[A-Za-z0-9_.]+$/'
            ],
            'youtube_url' => [
                'nullable',
                'regex:/^(https?:\/\/)?(www\.)?youtube\.com\/(c\/|channel\/|@|user\/)?[A-Za-z0-9_\-]+$/'
            ],
            'linkedin_url' => [
                'nullable',
                'regex:/^(https?:\/\/)?(www\.)?linkedin\.com\/(in|company)\/[A-Za-z0-9._%-]+$/'
            ],
        ], [
            'facebook_url.regex' => 'Enter a valid Facebook URL like https://facebook.com/yourpage',
            'twitter_url.regex' => 'Enter a valid X URL like https://x.com/username',
            'instragram_url.regex' => 'Enter a valid Instagram URL like https://instagram.com/username',
            'youtube_url.regex' => 'Enter a valid YouTube URL like https://youtube.com/@channelname',
            'linkedin_url.regex' => 'Enter a valid LinkedIn URL like https://linkedin.com/in/username or https://linkedin.com/company/companyname',
        ]);

        $business = Business::find($request->business_id);
        $business->business_name = $request->business_name;
        $business->category_id = $request->category_id;
        $business->country_code = $request->country_code;
        $business->business_phone = $request->business_phone;
        $business->business_email = $request->business_email;
        $business->youtube_url = $request->youtube_url;
        $business->twitter_url = $request->twitter_url;
        $business->instragram_url = $request->instragram_url;
        $business->linkedin_url = $request->linkedin_url;
        $business->facebook_url = $request->facebook_url;
        $business->business_desc = $request->business_desc;
        $business->save();
        return redirect()->back()->with('success', 'Business updated successfully.');
    }

    public function edit_business_address_action(Request $request)
    {
        BusinessAddress::updateOrCreate(
            ['business_id' => $request->business_id],
            [
                'street_line_1'            => $request->street_line_1,
                'street_line_2'            => $request->street_line_2,
                'city'                     => $request->city,
                'province_state_territory' => $request->province_state_territory,
                'ref_country_id'           => $request->ref_country_id,
                'postal_code'              => $request->postal_code,
                'ref_address_type_id' => 2
            ]
        );
        return redirect()->back()->with('success', 'Business address updated successfully.');
    }

    public function edit_business_hours_action(Request $request)
    {
        $business = Business::where('business_id', $request->business_id)->first();
        $day_names = [
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday',
        ];

        foreach (range(1, 7) as $day) {

            $isClosed = isset($request->closed[$day]);

            if ($isClosed) {
                BusinessCustomHour::updateOrInsert(
                    [
                        'business_id' => $business->business_id,
                        'day_of_week' => $day,
                    ],
                    [
                        'name'       => $day_names[$day],
                        'is_closed'  => 1,
                        'start_ts'   => null,
                        'end_ts'     => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                continue;
            }

            if (!isset($request->start[$day]) || !isset($request->end[$day])) {
                continue;
            }

            BusinessCustomHour::updateOrInsert(
                [
                    'business_id' => $business->business_id,
                    'day_of_week' => $day,
                ],
                [
                    'name'       => $day_names[$day],
                    'is_closed'  => 0,
                    'start_ts'   => date("H:i:s", strtotime($request->start[$day])),
                    'end_ts'     => date("H:i:s", strtotime($request->end[$day])),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        return redirect()->back()->with('success', 'Business hours updated successfully.');
    }

    public function edit_user_action($id, Request $request)
    {
        $edit_user = User::where('id', $id)->first();
        $edit_user->first_name = $request->first_name;
        $edit_user->last_name = $request->last_name;
        $edit_user->email = $request->email;
        $edit_user->is_active = $request->is_active;
        $edit_user->save();
        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function business_approve(Business $business)
    {
        $business->update([
            'is_admin_verified' => 1,
        ]);

        return redirect()->route('business_list')->with('success', 'Business approved successfully.');
    }

    public function business_reject(Business $business)
    {
        $business->update([
            'is_admin_verified' => 2,
        ]);

        return redirect()->route('business_list')->with('success', 'Business rejected successfully.');
    }

    public function business_list_filter(Request $request)
    {
        $currentPage = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $query = Business::leftJoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->select('businesses.*', 'categories.cat_name as category_name');

        if ($request->filled('business_verified')) {
            $query->where('businesses.is_claimed', $request->business_verified);
        }

        if ($request->filled('admin_verified')) {
            $query->where('businesses.is_admin_verified', $request->admin_verified);
        }

        if ($request->filled('is_admin_posted')) {
            $query->where('businesses.is_admin_posted', $request->is_admin_posted);
        }

        if ($request->filled('category_id')) {
            $query->where('businesses.category_id', $request->category_id);
        }

        if ($request->filled('business_url')) {
            $query->where('businesses.business_website', 'like', '%' . $request->business_url . '%');
        }

        if ($request->filled('business_email')) {
            $query->where('businesses.business_email', 'like', '%' . $request->business_email . '%');
        }

        if ($request->filled('payment_status')) {
            $query->where('businesses.business_status', $request->payment_status);
        }

        // From & To date filter (created_at)
        if ($request->filled('from_date') && $request->filled('to_date')) {

            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('businesses.created_at', [$fromDate, $toDate]);
        } elseif ($request->filled('from_date')) {

            $query->whereDate('businesses.created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {

            $query->whereDate('businesses.created_at', '<=', $request->to_date);
        }

        $businesses = $query->orderBy('businesses.business_id', 'desc')->paginate(50);

        $html = '';

        if ($businesses->count()) {
            foreach ($businesses as $row) {

                $edit_url = route('edit_business', Crypt::encrypt($row->business_id));
                $view_url = $row->business_website;
                $html .= '<tr>';
                $html .= '<td>' . date('d-m-Y', strtotime($row->created_at)) . '</td>';
                $html .= '<td>' . e($row->business_name) . '</td>';
                $html .= '<td>' . e($row->category_name) . '</td>';
                $html .= '<td>' . status_badge($row->is_claimed) . '</td>';
                $html .= '<td>' . status_badge($row->is_admin_verified) . '</td>';
                $html .= '<td>' . payment_badge($row->business_status) . '</td>';

                $html .= '<td class="text-nowrap">
                    <a class="badge bg-info rounded rounded-circle"
                        style="padding-top:7px;padding-bottom:7px;"
                        href="' . $edit_url . '"
                        title="Edit business">
                        <i class="bi bi-pencil fs-9"></i>
                    </a>
                </td>';

                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>
            <td colspan="7" class="text-center">' . no_record_found_in_table() . '</td>
        </tr>';
        }

        $pagination = $businesses
            ->appends(array_merge($request->all(), ['page' => $currentPage]))
            ->links('pagination::bootstrap-5')
            ->render();

        return response()->json(['html' => $html, 'pagination' => $pagination, 'page' => $currentPage]);
    }

    public function old_business_list()
    {
        $old_businesses = OldBusiness::leftjoin('categories', 'old_businesses.category_id', '=', 'categories.category_id')
            ->select('old_businesses.*', 'categories.cat_name as category_name')->paginate(50);
        return view('old_business.index', compact('old_businesses'));
    }

    public function edit_old_business($id)
    {
        $decrypt_id = Crypt::decrypt($id);
        $edit_old_business = OldBusiness::leftjoin('old_categories', 'old_businesses.category_id', '=', 'old_categories.old_category_id')
            ->select('old_businesses.*', 'old_categories.cat_name as category_name')
            ->where('old_businesses.old_business_id', $decrypt_id)->first();

        $name_parts = explode(' ', trim($edit_old_business->name), 2);
        $edit_old_business->first_name = $name_parts[0] ?? '';
        $edit_old_business->last_name  = $name_parts[1] ?? '';

        // Normalize URL for form display
        $url = trim($edit_old_business->url);

        // Ensure scheme exists (parse_url fails without it)
        if (!preg_match('#^https?://#', $url)) {
            $url = 'http://' . $url;
        }

        $parsed = parse_url($url);

        $host = $parsed['host'] ?? '';
        $host = preg_replace('/^www\./i', '', $host);
        $scheme = 'https';

        // Final normalized URL (protocol + domain only)
        $edit_old_business->display_url = $host
            ? $scheme . '://' . $host
            : '';

        //Split email into username and domain
        $email_parts = explode('@', $edit_old_business->mail, 2);

        $edit_old_business->email_username = $email_parts[0] ?? '';
        $edit_old_business->email_domain   = $host ?: 'worldweb-directory.com';;

        $categories = Category::get();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);

        return view('old_business.edit', compact('edit_old_business', 'parents', 'children'));
    }

    public function old_business_action(Request $request, $id)
    {
        $decrypt_id = Crypt::decrypt($id);
        $old_business = OldBusiness::where('old_business_id', $decrypt_id)->firstOrFail();

        DB::transaction(function () use ($request, $old_business) {

            // Normalize URL
            $url = trim($request->url);
            if (!preg_match('#^https?://#', $url)) {
                $url = 'http://' . $url;
            }

            $parsed = parse_url($url);

            $host = $parsed['host'] ?? '';
            $host = preg_replace('/^www\./i', '', $host);

            $finalUrl = 'https://' . $host;

            // Domain-based email (FROM URL)
            $email = trim($request->email_username) . '@' . $host;

            $new_user = new User();
            $new_user->first_name = $request->first_name;
            $new_user->last_name = $request->last_name;
            $new_user->email = $email;
            $new_user->password = Hash::make('12345678');
            $new_user->save();

            $new_business = new Business();
            $new_business->business_name = $request->caption;
            $new_business->business_website = $finalUrl;
            $new_business->wwd_url = Str::slug($request->caption);
            $new_business->business_email = $email;
            $new_business->category_id = $request->new_category_id;
            $new_business->business_desc = $request->site_desc;
            $new_business->user_id = $new_user->id;
            $new_business->is_admin_verified = 1;
            $new_business->is_admin_posted = 1;
            $new_business->created_at = now();
            $new_business->save();

            // Delete old business
            $old_business->delete();
        });

        return redirect()->route('old_business_list')->with('success', 'Business approved and migrated successfully.');
    }

    public function check_business(Request $request)
    {
        $errors = [];

        // Check business name
        if (!empty($request->caption)) {
            $slug = Str::slug($request->caption);

            if (Business::where('wwd_url', $slug)->exists()) {
                $errors['caption'] = 'This business already exists.';
            }
        }

        // Check business URL
        if (!empty($request->url)) {
            $url = trim($request->url);

            if (!preg_match('#^https?://#', $url)) {
                $url = 'http://' . $url;
            }

            $parsed = parse_url($url);
            $finalUrl = 'https://' . ($parsed['host'] ?? '');

            if (Business::where('business_website', $finalUrl)->exists()) {
                $errors['url'] = 'This business URL already exists.';
            }
        }

        return response()->json([
            'status' => empty($errors) ? 'ok' : 'error',
            'errors' => $errors
        ]);
    }
}
