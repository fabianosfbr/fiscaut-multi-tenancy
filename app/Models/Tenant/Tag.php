<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tag extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $appends = ['namecode'];

    protected static function boot()
    {
        parent::boot();



        static::saved(function ($tag) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
            Cache::forget("category_tag_.{$tag->category->organization_id}._all");
        });

        static::deleted(function ($tag) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
            Cache::forget("category_tag_.{$tag->category->organization_id}._all");
        });

        static::updated(function ($tag) {
            $organizationId = auth()->user()->last_organization_id;
            $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;
            Cache::forget($cacheKey);
            Cache::forget("category_tag_.{$tag->category->organization_id}._all");
        });
    }

    public function category()
    {
        return $this->belongsTo(CategoryTag::class, 'category_id');
    }

    public function getNameCodeAttribute()
    {
        return "{$this->code} - {$this->name}";
    }

    public static function getTagsForFilter(): array
    {
        $organizationId = auth()->user()->last_organization_id;
        $cacheKey = 'taggeds_for_filter_organization_' . $organizationId;

        return Cache::rememberForever($cacheKey, function () use ($organizationId) {
            $tagUsed = self::getUsedTagIdsForFileUploads();

            $tags = self::whereHas('category', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
                ->where('is_enable', true)
                ->whereIn('id', $tagUsed)
                ->orderBy('name', 'asc')
                ->get()
                ->keyBy('id')
                ->map(fn($tag) => $tag->code . ' - ' . $tag->name)
                ->toArray();

            return $tags;
        });
    }
    private static function getUsedTagIdsForFileUploads()
    {
        return self::rightJoin('tagging_tagged', 'tags.id', '=', 'tagging_tagged.tag_id')
            ->where('taggable_type', FileUpload::class)
            ->select('tags.id')
            ->distinct()
            ->get()
            ->pluck('id');
    }
}
