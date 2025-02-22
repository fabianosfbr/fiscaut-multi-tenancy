<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
            Cache::forget("category_tag_.{$tag->category->organization_id}._all");
        });

        static::deleted(function ($tag) {
            Cache::forget("category_tag_.{$tag->category->organization_id}._all");
        });

        static::updated(function ($tag) {

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
}
