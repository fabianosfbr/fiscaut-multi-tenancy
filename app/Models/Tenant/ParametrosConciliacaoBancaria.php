<?php

namespace App\Models\Tenant;


use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParametrosConciliacaoBancaria extends Model
{
    use HasFactory;

    protected $with = ['plano_de_conta'];
    
    protected $table = 'contabil_parametros_gerais';

    protected $guarded = ['id'];


    protected $casts = [
        'params' => 'array',
        'descricao_conta_contabil' => 'array',
        'codigo' => 'array',
        'descricao' => 'array',
        'complemento_historico' => 'array',
        'descricao_historico' => 'array',
        'is_inclusivo' => 'boolean',
    ];

    
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

    //Plano de contas
    public function plano_de_conta()
    {
        return $this->belongsTo(PlanoDeConta::class, 'conta_contabil', 'id');
    }

    public function scopeSearchByParametro(Builder $query, string $search): Builder
    {
        return $query->whereRaw(
            "JSON_SEARCH(params COLLATE utf8mb4_general_ci, 'one', ? COLLATE utf8mb4_general_ci, null, '$') IS NOT NULL",
            ["%{$search}%"]
        );
    }

    /**
     * Obtém todas os parâmetros de uma organização com cache
     * 
     * @param string $organizationId ID da organização
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCachedByOrganization(string $organizationId)
    {
        $cacheKey = "parametros_conciliacao_bancaria_organization_id_{$organizationId}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($organizationId) {
            return static::where('organization_id', $organizationId)
                ->orderBy('order')
                ->get();
        });
    }

    
    /**
     * Limpa o cache de parâmetros de uma organização
     * 
     * @param string $organizationId ID da organização
     * @return void
     */
    public static function clearCacheByOrganization(string $organizationId): void
    {
        $cacheKey = "parametros_conciliacao_bancaria_organization_id_{$organizationId}";
        Cache::forget($cacheKey);
    }
}
