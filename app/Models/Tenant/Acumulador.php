<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Acumulador extends Model
{
    public $table = 'acumuladores';

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            Cache::forget("acumuladores_.{$item->organizationId}._all");
        });

        static::deleted(function ($item) {
            Cache::forget("acumuladores_.{$item->organizationId}._all");
        });

        static::updated(function ($item) {
            Cache::forget("acumuladores_.{$item->organizationId}._all");
        });
    }

    public static function getAll(string $organizationId)
    {
        return Cache::remember("acumuladores_.{$organizationId}._all", now()->addDay(), function () use ($organizationId) {
            return static::where('organization_id', $organizationId)
                ->get();
        });
    }
}
