<?php

namespace App\Helpers;

use App\Models\PermissionMst;
use App\Models\RiskRegister;
use App\Models\RolePermissionMap;
use App\Models\UserPermissionMap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Webhook
{
    public static function risk_matrix_logic_2($value = null, $financial_year_id = null, $division_id = null, $risk_owner = null)
    {
        $prob = Str::substr($value, 0, 1);
        $impact = Str::substr($value, -1);

        $count = RiskRegister::where('probability_id', $prob)
            ->where('impact_id', $impact)
            ->where('financial_year_id', $financial_year_id)
            ->when($division_id, fn($q) => $q->where('division_id', $division_id))
            ->when($risk_owner, fn($q) => $q->where('risk_owner_id', $risk_owner))
            ->count();
        if ($count > 0) {
            return sprintf(
                '<span class="risk-count text-white h3"
                    data-prob="%d"
                    data-impact="%d"
                    data-owner="%d"
                    data-division="%d"
                    data-fy="%d"
                    style="cursor:pointer;">%d</span>',
                $prob,
                $impact,
                $risk_owner,
                $division_id,
                $financial_year_id,
                $count
            );
        }

        return '';
    }


    public static function has_permission($permission_name)
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $permission = PermissionMst::where('permission_name', $permission_name)
            ->value('permission_id');

        if (!$permission) {
            return false;
        }

        // check user specific permissions
        $user_has = UserPermissionMap::where('user_id', $user->id)
            ->where('permission_id', $permission)
            ->exists();

        if ($user_has) {
            return true;
        }

        if ($user->role_id) {
            $role_has = RolePermissionMap::where('role_id', $user->role_id)
                ->where('permission_id', $permission)
                ->exists();

            return $role_has;
        }

        return false;
    }

    public static function get_risk_by_prob_impact($value, $risk_owner, $financial_year_id)
    {
        $probability_id = Str::substr($value, 0, 1);
        $impact_id = Str::substr($value, -1);
        return RiskRegister::where('probability_id', $probability_id)
            ->where('impact_id', $impact_id)
            ->where('risk_owner_id', $risk_owner)
            ->where('financial_year_id', $financial_year_id)
            ->count();
    }
}
