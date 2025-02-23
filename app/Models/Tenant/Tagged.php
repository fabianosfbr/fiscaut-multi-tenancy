<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tagged extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'tagging_tagged';

    public $timestamps = false;

    protected $cachePrefix = 'tagging_tagged';

    protected $guarded = ['id'];

    protected $casts = [
        'product' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();


        static::saved(function ($category) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
        });

        static::deleted(function ($category) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
        });

        static::updated(function ($category) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
        });
    }

    public function taggable()
    {
        return $this->morphTo();
    }

    public function tag()
    {

        return $this->belongsTo(Tag::class, 'tag_id', 'id');
    }

    public function tagNamesWithCode(): array
    {
        return $this->tagged->map(function ($item) {
            return $item->tag->code . ' - ' . $item->tag_name;
        })->toArray();
    }
}
