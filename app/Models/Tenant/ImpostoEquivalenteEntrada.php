<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ImpostoEquivalenteEntrada extends Model
{
    public $table = 'entradas_impostos_equivalentes';

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($value) {
            Cache::forget("entradas_impostos_equivalentes_.{$value->organizationId}");
        });

        static::deleted(function ($value) {
            Cache::forget("entradas_impostos_equivalentes_.{$value->organizationId}");
        });

        static::updated(function ($value) {
            Cache::forget("entradas_impostos_equivalentes_.{$value->organizationId}");
        });
    }

    public static function getAll(string $organizationId)
    {

        return Cache::remember("entradas_impostos_equivalentes_.{$organizationId}", now()->addDay(), function () use ($organizationId) {
            return static::where('organization_id', $organizationId)
                ->get();
        });
    }
}
