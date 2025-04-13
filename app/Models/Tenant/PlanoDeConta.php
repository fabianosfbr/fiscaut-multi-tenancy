<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanoDeConta extends Model
{
    protected $guarded = ['id'];

    protected $table = 'contabil_plano_de_contas';


    /**
     * Hook para limpar o cache quando o modelo é atualizado
     */
    protected static function booted()
    {
        static::saved(function ($model) {
            $model->clearCacheByOrganization($model->organization_id);
        });

        static::updated(function ($model) {
            $model->clearCacheByOrganization($model->organization_id);
        });

        static::deleted(function ($model) {
            $model->clearCacheByOrganization($model->organization_id);
        });


    }

    /**
     * Obtém todas as plano de contas de uma organização com cache
     * 
     * @param string $organizationId ID da organização
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedByOrganization(string $organizationId)
    {
        $cacheKey = "plano_de_contas_organization_id_{$organizationId}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($organizationId) {
            return static::where('organization_id', $organizationId)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }


    /**
     * Limpa o cache de plano de contas de uma organização
     * 
     * @param string $organizationId ID da organização
     * @return void
     */
    public static function clearCacheByOrganization(string $organizationId): void
    {
        $cacheKey = "plano_de_contas_organization_id_{$organizationId}";
        Cache::forget($cacheKey);
    }
}
