<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Observers\Tenant\CategoryTagObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;


#[ObservedBy([CategoryTagObserver::class])]
class CategoryTag extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($category) {
            Cache::forget("category_tag_.{$category->organizationId}._all");
        });

        static::deleted(function ($category) {
            Cache::forget("category_tag_.{$category->organizationId}._all");
        });

        static::updated(function ($category) {
            Cache::forget("category_tag_.{$category->organizationId}._all");
        });
    }


    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'category_id');
    }



    public static function getAllEnabled(string $organizationId)
    {

        return Cache::remember("category_tag_.{$organizationId}._all", now()->addDay(), function () use ($organizationId) {
            return static::with('tags')
                ->where('organization_id', $organizationId)
                ->where('is_enable', true)
                ->orderBy('order', 'asc')
                ->get();
        });
    }
}
