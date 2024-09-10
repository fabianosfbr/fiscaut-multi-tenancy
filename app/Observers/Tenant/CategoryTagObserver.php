<?php

namespace App\Observers\Tenant;


use Filament\Facades\Filament;
use App\Models\Tenant\CategoryTag;
use Illuminate\Support\Facades\Cache;

class CategoryTagObserver
{
    public function creating(CategoryTag $model)
    {
        // $organization = Filament::getTenant();

        // if ($organization) {
        //     $model->organization_id = $organization->id;
        // }
    }
    /**
     * Handle the CategoryTag "created" event.
     */
    public function created(CategoryTag $categoryTag): void
    {
    }

    /**
     * Handle the CategoryTag "updated" event.
     */
    public function updated(CategoryTag $categoryTag): void
    {
        Cache::forget('categoryWithDifal_' . $categoryTag->organization_id);
        Cache::forget('category_with_tag_for_searching_' . $categoryTag->organization_id);
        Cache::forget('categorias_issuer_' . $categoryTag->organization_id);
    }

    /**
     * Handle the CategoryTag "deleted" event.
     */
    public function deleted(CategoryTag $categoryTag): void
    {
        //
    }

    /**
     * Handle the CategoryTag "restored" event.
     */
    public function restored(CategoryTag $categoryTag): void
    {
        //
    }

    /**
     * Handle the CategoryTag "force deleted" event.
     */
    public function forceDeleted(CategoryTag $categoryTag): void
    {
        //
    }
}
