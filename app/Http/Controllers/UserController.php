<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Business users own at least one business; everyone else is a guest user. Admins are excluded.
    public function users(Request $request)
    {
        $since = now()->subDays(30);

        $owners = Business::select('user_id')
            ->selectRaw('MIN(business_id) as business_id, COUNT(*) as business_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $base = User::leftJoinSub($owners, 'owner', 'owner.user_id', '=', 'users.id')
            ->where('users.is_admin', 0);

        $stats = (clone $base)->selectRaw(
            'COUNT(*) as total,
            SUM(owner.user_id IS NOT NULL) as business,
            SUM(owner.user_id IS NULL) as guest,
            SUM(users.is_active = 1) as activated,
            SUM(users.last_login_at >= ?) as active_30,
            SUM(users.last_login_at IS NOT NULL) as ever_signed_in,
            SUM(users.created_at >= ?) as new_30,
            SUM(users.google_id IS NOT NULL) as google',
            [$since, $since]
        )->first();

        $type = in_array($request->type, ['business', 'guest']) ? $request->type : 'all';

        $users = (clone $base)
            ->leftJoin('businesses', 'businesses.business_id', '=', 'owner.business_id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.google_id', 'users.google_avatar',
                'users.is_active', 'users.status', 'users.last_login_at', 'users.created_at',
                'owner.business_count', 'businesses.business_id', 'businesses.business_name', 'businesses.business_website', 'businesses.is_claimed')
            ->when($type === 'business', fn($q) => $q->whereNotNull('owner.user_id'))
            ->when($type === 'guest', fn($q) => $q->whereNull('owner.user_id'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('users.email', 'like', $term)
                        ->orWhereRaw("CONCAT_WS(' ', users.first_name, users.last_name) like ?", [$term])
                        ->orWhere('businesses.business_name', 'like', $term)
                        ->orWhere('businesses.business_website', 'like', $term);
                });
            })
            ->when($request->account === 'activated', fn($q) => $q->where('users.is_active', 1))
            ->when($request->account === 'not_activated', fn($q) => $q->where('users.is_active', '!=', 1))
            ->when($request->activity === 'active_30', fn($q) => $q->where('users.last_login_at', '>=', $since))
            ->when($request->activity === 'never', fn($q) => $q->whereNull('users.last_login_at'))
            ->when($request->status === 'enabled', fn($q) => $q->where('users.status', 1))
            ->when($request->status === 'disabled', fn($q) => $q->where('users.status', '!=', 1))
            ->orderBy('users.id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $has_filters = $request->filled('q') || $request->filled('account') || $request->filled('activity') || $request->filled('status');

        return view('users.index', compact('users', 'stats', 'type', 'has_filters'));
    }

    public function users_status_update(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        return response()->json([
            'status' => $user->status,
            'badge' => users_status($user->status),
        ]);
    }
}
