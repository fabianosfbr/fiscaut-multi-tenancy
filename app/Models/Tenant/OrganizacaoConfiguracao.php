<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        // Log dos dados recebidos
        Log::info('OrganizacaoConfiguracao::salvarConfiguracao - Dados recebidos:', [
            'organizationId' => $organizationId,
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'categoria' => $categoria
        ]);
        
        try {
            // Encontrar ou criar o registro
            $model = static::updateOrCreate(
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
            
            // Log após salvar
            Log::info('OrganizacaoConfiguracao::salvarConfiguracao - Salvou com sucesso', [
                'id' => $model->id,
                'itens' => isset($model->configuracoes['itens']) ? count($model->configuracoes['itens']) : 0
            ]);
            
            return $model;
        } catch (\Exception $e) {
            // Log de erro
            Log::error('Erro ao salvar configuração: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Tenta salvar usando query bruta em caso de falha
            try {
                // Preparar JSON manualmente
                $jsonData = json_encode($configuracoes);
                
                // Buscar registro existente
                $existingRecord = static::where('organization_id', $organizationId)
                    ->where('tipo', $tipo)
                    ->where('subtipo', $subtipo)
                    ->where('categoria', $categoria)
                    ->first();
                
                if ($existingRecord) {
                    // Atualizar registro existente
                    DB::statement("
                        UPDATE organizacao_configuracoes 
                        SET configuracoes = ?, updated_at = NOW() 
                        WHERE id = ?
                    ", [$jsonData, $existingRecord->id]);
                    
                    return $existingRecord->fresh();
                } else {
                    // Criar novo registro
                    $uuid = (string) Str::uuid();
                    $now = now()->format('Y-m-d H:i:s');
                    
                    DB::statement("
                        INSERT INTO organizacao_configuracoes 
                        (id, organization_id, tipo, subtipo, categoria, configuracoes, ativo, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
                    ", [$uuid, $organizationId, $tipo, $subtipo, $categoria, $jsonData, $now, $now]);
                    
                    return static::find($uuid);
                }
            } catch (\Exception $innerException) {
                Log::error('Erro no fallback ao salvar configuração: ' . $innerException->getMessage());
                throw $innerException;
            }
        }
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