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
        $total_business = Business::count();
        $verified_business = Business::where('is_claimed', 1)->count();
        $unverified_business = Business::where('is_claimed', 0)->count();
        $todays_business = Business::whereDate('created_at', Carbon::today())->count();
        $approved_business = Business::where('is_admin_verified', 1)->count();
        $featured_business = Business::where('business_status', 'P')->count();

        $dates = [];
        $total_submissions = [];
        $paid_submissions = [];

        $start_date = Carbon::today()->subDays(9);
        $end_date   = Carbon::today();

        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');

            $dates[] = Carbon::parse($date)->format('d M');

            // Total business submissions
            $total_submissions[] = Business::whereDate('created_at', $date)->count();

            // Paid businesses
            $paid_submissions[] = Business::whereDate('created_at', $date)
                ->where('business_status', 'P')
                ->count();
        }

        return view('index', compact('total_business', 'verified_business', 'unverified_business', 'todays_business', 'approved_business', 'featured_business', 'dates', 'total_submissions', 'paid_submissions', 'start_date', 'end_date'));
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
            return redirect()->back()->with('error', 'Invalid Email Or Password.');
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
