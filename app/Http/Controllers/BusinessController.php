<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessAddress;
use App\Models\BusinessCustomHour;
use App\Models\Category;
use App\Models\OldBusiness;
use App\Models\RefCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

use function Laravel\Prompts\select;

class BusinessController extends Controller
{
    public function business_list()
    {
        $business = Business::leftjoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->select('businesses.*', 'categories.cat_name as category_name')->paginate(10);
        $categories = Category::get();
        $parents = $categories->where('parent_cat_id', 0);
        $children = $categories->where('parent_cat_id', '!=', 0);
        return view('business.index', compact('business', 'categories', 'parents', 'children'));
    }

    public function edit_business($id)
    {
        $decrypt_id = decrypt($id);
        $edit_business = Business::leftjoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->select('businesses.*', 'categories.cat_name as category_name')
            ->find($decrypt_id);
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
        return view('business.edit', compact('edit_business', 'categories', 'parents', 'children', 'countries', 'business_address', 'ref_countries', 'hours', 'has_hours', 'times'));
    }

    public function edit_business_action(Request $request)
    {
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
        $business->business_desc = $request->business_desc;
        $business->save();
        return redirect()->back()->with('success', 'Business updated successfully');
    }

    public function edit_business_address_action(Request $request)
    {
        $business_address = BusinessAddress::where('business_id', $request->business_id)->first();
        $business_address->street_line_1 = $request->street_line_1;
        $business_address->street_line_2 = $request->street_line_2;
        $business_address->city = $request->city;
        $business_address->province_state_territory = $request->province_state_territory;
        $business_address->ref_country_id = $request->ref_country_id;
        $business_address->postal_code = $request->postal_code;
        $business_address->save();
        return redirect()->back()->with('success', 'Business address updated successfully');
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
        $query = Business::leftJoin('categories', 'businesses.category_id', '=', 'categories.category_id')
            ->select('businesses.*', 'categories.cat_name as category_name');

        if ($request->filled('business_verified')) {
            $query->where('businesses.is_claimed', $request->business_verified);
        }

        if ($request->filled('admin_verified')) {
            $query->where('businesses.is_admin_verified', $request->admin_verified);
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

        $businesses = $query->get();

        $html = '';

        if ($businesses->count()) {
            foreach ($businesses as $row) {

                $edit_url = route('edit_business', Crypt::encrypt($row->business_id));
                $view_url = $row->business_website;
                $html .= '<tr>';
                $html .= '<td>' . date('d-m-Y', strtotime($row->created_at)) . '</td>';
                $html .= '<td>' . e($row->business_name) . '</td>';
                $html .= '<td>' . e($row->business_email) . '</td>';
                $html .= '<td>' . e($row->category_name) . '</td>';
                $html .= '<td>' . status_badge($row->is_claimed) . '</td>';
                $html .= '<td>' . status_badge($row->is_admin_verified) . '</td>';

                $html .= '<td class="text-nowrap">
                    <a class="badge bg-info rounded rounded-circle"
                        style="padding-top:7px;padding-bottom:7px;"
                        href="' . $edit_url . '"
                        title="Edit business">
                        <i class="bi bi-pencil fs-9"></i>
                    </a>

                    <a class="badge bg-warning rounded rounded-circle"
                        style="padding-top:7px;padding-bottom:7px;"
                        href="' . $view_url . '"
                        target="_blank"
                        title="View site">
                        <i class="bi bi-eye fs-9"></i>
                    </a>
                </td>';

                $html .= '</tr>';
            }
        } else {
            $html .= '<tr>
            <td colspan="7" class="text-center">' . no_record_found_in_table() . '</td>
        </tr>';
        }

        return response()->json(['html' => $html]);
    }

    public function old_business_list()
    {
        $old_businesses = OldBusiness::leftjoin('categories', 'old_businesses.category_id', '=', 'categories.category_id')
            ->select('old_businesses.*', 'categories.cat_name as category_name')->paginate(10);
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
        $scheme = 'https';

        // Final normalized URL (protocol + domain only)
        $edit_old_business->display_url = $host
            ? $scheme . '://' . $host
            : '';

        //Split email into username and domain
        $email_parts = explode('@', $edit_old_business->mail, 2);

        $edit_old_business->email_username = $email_parts[0] ?? '';
        $edit_old_business->email_domain   = $email_parts[1] ?? 'gmail.com';
        return view('old_business.edit', compact('edit_old_business'));
    }
}
