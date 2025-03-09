<?php

use App\Models\Tenant\Tag;
use App\Models\Tenant\FileUpload;
use Saloon\XmlWrangler\XmlReader;
use App\Models\Tenant\CategoryTag;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

if (! function_exists('getOrganizationCached')) {
    function getOrganizationCached()
    {
        $user = Auth::user();

        return Organization::getCached($user->last_organization_id, $user->id);
    }
}

if (! function_exists('getAllValidOrganizationsForUser')) {
    function getAllValidOrganizationsForUser($user)
    {

        return Cache::remember('all_valid_organizations_for_user_' . $user->id, 10, function () use ($user) {
            return Organization::whereHas('users', function ($q) use ($user) {
                $q->where('is_active', 1)->where('user_id', $user->id);
            })->get();
        });
    }
}

if (! function_exists('categoryWithTagForSearching')) {
    function categoryWithTagForSearching($organization_id)
    {
        return Cache::remember('category_with_tag_for_searching_' . $organization_id, 10, function () use ($organization_id) {
            return CategoryTag::with('tags')
                ->where('organization_id', $organization_id)
                ->where('is_enable', true)
                ->orderBy('order', 'asc')
                ->get();
        });
    }
}

if (! function_exists('loadXmlReader')) {
    function loadXmlReader($xml)
    {
        return XmlReader::fromString($xml);
    }
}

if (! function_exists('searchValueInArray')) {
    function searchValueInArray(array $data, $needle)
    {
        $iterator = new RecursiveArrayIterator($data);
        $recursive = new RecursiveIteratorIterator(
            $iterator,
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
        }

        return null;
    }
}

if (! function_exists('money_formatter')) {
    function money_formatter($valor)
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

if (! function_exists('getLabelTag')) {
    function getLabelTag($str)
    {
        $acronym = null;
        $word = null;

        $words = preg_split("/(\s|\-|\.)/", $str);
        foreach ($words as $w) {
            $acronym .= substr($w, 0, 1);
        }
        $word = $word . $acronym;

        return $word;
    }
}


