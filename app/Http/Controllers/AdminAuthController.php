<?php

namespace App\Http\Controllers;

use App\Helpers\Webhook;
use App\Models\DivisionMst;
use App\Models\FinancialYear;
use App\Models\RiskRegister;
use App\Models\User;
use App\Models\UserDivisionMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function dashboard()
    {
        return view('index');
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
            if ($user->is_active == 1) {
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
