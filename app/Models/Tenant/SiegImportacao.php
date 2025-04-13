<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiegImportacao extends Model
{
    protected $table = 'sieg_importacoes';

    protected $guarded = ['id'];

    protected $casts = [
        'data_inicial' => 'date',
        'data_final' => 'date',
        'sucesso' => 'boolean',
        'download_eventos' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtém todas as importações de uma organização com cache
     * 
     * @param string $organizationId ID da organização
     * @param int $minutes Tempo de cache em minutos
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedByOrganization(string $organizationId, int $minutes = 60)
    {
        $cacheKey = "sieg_importacoes:org:{$organizationId}";
        
        return Cache::remember($cacheKey, now()->addMinutes($minutes), function () use ($organizationId) {
            return static::where('organization_id', $organizationId)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Obtém uma importação específica com cache
     * 
     * @param string $id ID da importação
     * @param int $minutes Tempo de cache em minutos
     * @return \App\Models\Tenant\SiegImportacao|null
     */
    public static function getCachedById(string $id, int $minutes = 60)
    {
        $cacheKey = "sieg_importacao:{$id}";
        
        return Cache::remember($cacheKey, now()->addMinutes($minutes), function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Limpa o cache de importações de uma organização
     * 
     * @param string $organizationId ID da organização
     * @return void
     */
    public static function clearCacheByOrganization(string $organizationId): void
    {
        $cacheKey = "sieg_importacoes:org:{$organizationId}";
        Cache::forget($cacheKey);
    }

    /**
     * Limpa o cache de uma importação específica
     * 
     * @param string $id ID da importação
     * @return void
     */
    public static function clearCacheById(string $id): void
    {
        $cacheKey = "sieg_importacao:{$id}";
        Cache::forget($cacheKey);
    }

    /**
     * Hook para limpar o cache quando o modelo é atualizado
     */
    protected static function booted()
    {
        static::saved(function ($model) {
            $model->clearCacheByOrganization($model->organization_id);
            $model->clearCacheById($model->id);
        });

        static::deleted(function ($model) {
            $model->clearCacheByOrganization($model->organization_id);
            $model->clearCacheById($model->id);
        });
    }
}
