<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class OrganizacaoConfiguracao extends Model
{
    use HasUuids;

    protected $table = 'organizacao_configuracoes';

    protected $fillable = [
        'organization_id',
        'tipo',
        'subtipo',
        'categoria',
        'ativo',
        'configuracoes',
    ];

    protected $casts = [
        'configuracoes' => 'array',
        'ativo' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($config) {
            static::limparCache($config);
        });

        static::deleted(function ($config) {
            static::limparCache($config);
        });

        static::updated(function ($config) {
            static::limparCache($config);
        });
    }

    /**
     * Relação com a organização
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Limpa o cache de configurações
     */
    protected static function limparCache($config): void
    {
        $cacheKey = "org.{$config->organization_id}.config.{$config->tipo}";
        
        if ($config->subtipo) {
            $cacheKey .= ".{$config->subtipo}";
            
            if ($config->categoria) {
                $cacheKey .= ".{$config->categoria}";
            }
        }
        
        Cache::forget($cacheKey);
        Cache::forget("org.{$config->organization_id}.config.all");
    }

    /**
     * Retorna configurações específicas
     */
    public static function obterConfiguracao(
        string $organizationId, 
        string $tipo, 
        ?string $subtipo = null, 
        ?string $categoria = null,
        array $padroes = []
    ): array {
        $cacheKey = "org.{$organizationId}.config.{$tipo}";
        
        if ($subtipo) {
            $cacheKey .= ".{$subtipo}";
            
            if ($categoria) {
                $cacheKey .= ".{$categoria}";
            }
        }

        return Cache::remember($cacheKey, now()->addDay(), function () use ($organizationId, $tipo, $subtipo, $categoria, $padroes) {
            $query = static::where('organization_id', $organizationId)
                ->where('tipo', $tipo)
                ->where('ativo', true);
            
            if ($subtipo) {
                $query->where('subtipo', $subtipo);
                
                if ($categoria) {
                    $query->where('categoria', $categoria);
                }
            }
            
            $config = $query->first();
            
            return $config ? array_merge($padroes, $config->configuracoes) : $padroes;
        });
    }

    /**
     * Salva as configurações
     */
    public static function salvarConfiguracao(
        string $organizationId, 
        string $tipo, 
        array $configuracoes,
        ?string $subtipo = null, 
        ?string $categoria = null
    ): self {
        return static::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'tipo' => $tipo,
                'subtipo' => $subtipo,
                'categoria' => $categoria,
            ],
            [
                'configuracoes' => $configuracoes,
                'ativo' => true,
            ]
        );
    }

    /**
     * Atualiza configurações específicas mantendo as demais
     */
    public static function atualizarConfiguracao(
        string $organizationId, 
        string $tipo, 
        array $configuracoes,
        ?string $subtipo = null, 
        ?string $categoria = null
    ): self {
        $query = static::where('organization_id', $organizationId)
            ->where('tipo', $tipo);
        
        if ($subtipo) {
            $query->where('subtipo', $subtipo);
            
            if ($categoria) {
                $query->where('categoria', $categoria);
            }
        }
        
        $config = $query->first();
        
        if ($config) {
            $configuracoesAtuais = $config->configuracoes;
            $novasConfiguracoes = array_merge($configuracoesAtuais, $configuracoes);
            $config->update(['configuracoes' => $novasConfiguracoes]);
            return $config;
        }
        
        return static::salvarConfiguracao($organizationId, $tipo, $configuracoes, $subtipo, $categoria);
    }

    /**
     * Limpa todas as configurações da organização
     */
    public static function limparTodasConfiguracoes(string $organizationId): void
    {
        $configs = static::where('organization_id', $organizationId)->get();
        
        foreach ($configs as $config) {
            static::limparCache($config);
        }
        
        static::where('organization_id', $organizationId)->delete();
    }
} 