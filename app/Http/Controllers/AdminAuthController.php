<?php

namespace App\Http\Controllers;

use App\Helpers\Webhook;
use App\Models\Business;
use App\Models\DivisionMst;
use App\Models\FinancialYear;
use App\Models\RiskRegister;
use App\Models\User;
use App\Models\UserDivisionMapping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function dashboard()
    {
        $start_date = Carbon::today()->subDays(9);
        $end_date   = Carbon::today();
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $month_ago = Carbon::now()->subDays(30);

        // All headline counts in one query
        $stats = Business::selectRaw(
            'COUNT(*) as total,
            SUM(is_claimed = 1) as verified,
            SUM(is_claimed = 0) as unverified,
            SUM(DATE(created_at) = ?) as today,
            SUM(DATE(created_at) = ?) as yesterday,
            SUM(DATE(created_at) = ? AND is_admin_verified = 0) as today_pending,
            SUM(created_at >= ?) as new_30,
            SUM(is_admin_verified = 1) as approved,
            SUM(is_admin_verified = 0) as pending_review,
            SUM(business_status = ?) as featured',
            [$today, $yesterday, $today, $month_ago, 'P']
        )->first();

        $total_business = (int) $stats->total;
        $verified_business = (int) $stats->verified;
        $unverified_business = (int) $stats->unverified;
        $todays_business = (int) $stats->today;
        $approved_business = (int) $stats->approved;
        $pending_review = (int) $stats->pending_review;
        $featured_business = (int) $stats->featured;

        // Daily submissions for the last 10 days in one grouped query
        $daily = Business::selectRaw('DATE(created_at) as day, COUNT(*) as total, SUM(business_status = ?) as paid', ['P'])
            ->where('created_at', '>=', $start_date)
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $dates = [];
        $total_submissions = [];
        $paid_submissions = [];

        for ($i = 9; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $row = $daily->get($day->toDateString());

            $dates[] = $day->format('d M');
            $total_submissions[] = $row ? (int) $row->total : 0;
            $paid_submissions[] = $row ? (int) $row->paid : 0;
        }

        $recent_businesses = Business::select('business_id', 'business_name', 'is_claimed', 'is_admin_verified', 'created_at')
            ->orderBy('business_id', 'desc')
            ->limit(6)
            ->get();

        // Customer accounts (admins excluded): business users own a business, the rest are guests
        $user_stats = User::where('is_admin', 0)
            ->selectRaw(
                'COUNT(*) as total,
                SUM(EXISTS (SELECT 1 FROM businesses WHERE businesses.user_id = users.id)) as business,
                SUM(is_active = 1) as activated,
                SUM(created_at >= ?) as new_30,
                SUM(DATE(created_at) = ?) as today,
                SUM(DATE(created_at) = ?) as yesterday,
                SUM(DATE(created_at) = ? AND EXISTS (SELECT 1 FROM businesses WHERE businesses.user_id = users.id)) as today_business',
                [$month_ago, $today, $yesterday, $today]
            )
            ->first();

        $total_users = (int) $user_stats->total;
        $business_users = (int) $user_stats->business;
        $guest_users = $total_users - $business_users;
        $new_users = (int) $user_stats->new_30;
        $activated_users = (int) $user_stats->activated;
        $new_businesses = (int) $stats->new_30;

        // Today vs yesterday (server time, UTC)
        $today_stats = [
            'businesses'           => $todays_business,
            'businesses_yesterday' => (int) $stats->yesterday,
            'businesses_pending'   => (int) $stats->today_pending,
            'users'                => (int) $user_stats->today,
            'users_yesterday'      => (int) $user_stats->yesterday,
            'users_business'       => (int) $user_stats->today_business,
        ];

        return view('index', compact('total_business', 'verified_business', 'unverified_business', 'todays_business', 'approved_business', 'pending_review', 'featured_business', 'dates', 'total_submissions', 'paid_submissions', 'start_date', 'end_date', 'recent_businesses', 'total_users', 'business_users', 'guest_users', 'new_users', 'activated_users', 'new_businesses', 'today_stats'));
    }

    public function admin_login()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function admin_login_action(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $email = $request->email;
        $password = $request->password;

        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            if ($user->is_active == 1 && $user->is_admin == 1) {
                Auth::login($user);
                return redirect()->intended('dashboard');
            } else {
                return redirect()->route('login')->with('error', 'Your account is inactive.');
            }
        } else {
            return redirect()->back()->withInput($request->only('email'))->with('error', 'Invalid email or password.');
        }
    }

    public function logout()
    {
        session()->flush();
        Auth::logout();
        return redirect()->route('login');
    }

    public function change_password(Request $request)
    {
        $validated = $request->validate(
            [
                'old_password' => 'required|min:8|string',
                'password' => 'required|min:8|string',
                'password_confirmation' => 'required|min:8|same:password',
            ],
            [
                'old_password.required' => 'Old password field is required',
                'password.required' => 'Password field is required',
                'password_confirmation.required' => 'Confirm password field is required',
                'password_confirmation.same' => 'Password & Confirm Password does not match.'
            ]
        );

        $hashed_password = Auth::user()->password;
        if (Hash::check($request->old_password, $hashed_password)) {
            $user = User::find(Auth::id());
            $user->password = Hash::make($request->password);
            $user->save();
            Auth::logout();
            session()->flash('success', 'Password changed successfully.');
            return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Old Password does not match, Please try again.'], 422);
        }
    }

    public function update_profile(Request $request)
    {
        $user = User::find(Auth::id());
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email_addr = $request->email;

        // if ($request->hasFile('profile_pic')) {
        //     $file = $request->file('profile_pic');
        //     $filename = rand() . '_' . $file->getClientOriginalName();

        //     $destinationPath = 'profile_pics';

        //     if (!file_exists($destinationPath)) {
        //         mkdir($destinationPath, 0777, true);
        //     }

        //     $file->move($destinationPath, $filename);
        //     $user->profile_pic = $destinationPath . '/' . $filename;
        // }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
