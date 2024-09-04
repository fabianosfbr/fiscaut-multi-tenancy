<?php

use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Cache;

if (!function_exists("getTenant")) {
    function getTenant($last_organization_id = null)
    {

        if ($last_organization_id) {
            return Organization::find($last_organization_id);
        }
        return Organization::find(Auth()->user()->last_organization_id);
    }
}


if (!function_exists("getAllValidOrganizationsForUser")) {
    function getAllValidOrganizationsForUser($user)
    {

        return Cache::remember("all_valid_organizations_for_user_" . $user->id, 10, function () use ($user) {
            return Organization::whereHas('users', function ($q) use ($user) {
                $q->where('is_active', 1)->where('user_id', $user->id);
            })->get();
        });
    }
}
