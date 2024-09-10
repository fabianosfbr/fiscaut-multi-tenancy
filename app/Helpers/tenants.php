<?php

use App\Models\Tenant\CategoryTag;
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


if (!function_exists("categoryWithTagForSearching")) {
    function categoryWithTagForSearching($organization_id)
    {
        return Cache::remember("category_with_tag_for_searching_" . $organization_id, 10, function () use ($organization_id) {
            return CategoryTag::with('tags')
                ->where('organization_id', $organization_id)
                ->where('is_enable', true)
                ->orderBy('order', 'asc')
                ->get();
        });
    }
}
