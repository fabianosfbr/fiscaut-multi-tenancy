<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ConfiguracaoGeral extends Model
{
    protected $table = 'configuracoes_gerais';

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($config) {
            Cache::forget("config.{$config->organization_id}.{$config->key}");
        });

        static::deleted(function ($config) {
            Cache::forget("config.{$config->organization_id}.{$config->key}");
        });

        static::updated(function ($config) {
            Cache::forget("config.{$config->organization_id}.{$config->key}");
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->castValue($value, $this->type);
            },
        );
    }

    /**
     * Casts the value based on the specified type.
     *
     * @param mixed  $value
     * @param string $type
     * @return mixed
     */
    private function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'float' => (float)$value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value
        };
    }

    public static function setValue(string $key, $value, string $organizationId): self
    {
        $processed = self::detectAndCastValue($value);

        return static::updateOrCreate(
            [
                'key' => $key,
                'organization_id' => $organizationId
            ],
            [
                'value' => match ($processed['type']) {
                    'array', 'json' => json_encode($processed['value']),
                    'boolean' => $processed['value'] ? 'true' : 'false',
                    default => (string) $processed['value']
                },
                'type' => $processed['type']
            ]
        );
    }

    public static function getValue(string $key, string $organizationId, $default = null)
    {
        $cacheKey = "config.{$organizationId}.{$key}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $organizationId, $default) {
            return static::where('key', $key)
                ->where('organization_id', $organizationId)
                ->first()
                ?->value ?? $default;
        });
    }

    public static function getMany(string $organizationId, ?array $keys = null,  array $defaults = []): array
    {
        // Se não foram especificadas chaves, busca todas as configurações da organização
        if (is_null($keys)) {
            return static::where('organization_id', $organizationId)
                ->get()
                ->mapWithKeys(function ($config) {
                    // Usa o accessor do modelo para converter o valor ao tipo correto
                    return [$config->key => $config->value];
                })
                ->all();
        }

        return collect($keys)->mapWithKeys(function ($key) use ($organizationId, $defaults) {
            return [$key => static::getValue($key, $organizationId, $defaults[$key] ?? null)];
        })->all();
    }

    // Método para limpar cache por organização
    public static function clearOrganizationCache(string $organizationId): void
    {
        static::where('organization_id', $organizationId)
            ->get()
            ->each(function ($config) {
                Cache::forget("config.{$config->organization_id}.{$config->key}");
            });

        Cache::forget("config.{config.{$organizationId}.all");
    }

    private static function detectAndCastValue($value): array
    {
        // Se já é um tipo primitivo, retorna ele mesmo com seu tipo
        if (!is_string($value)) {
            return [
                'value' => $value,
                'type' => match (true) {
                    is_bool($value) => 'boolean',
                    is_int($value) => 'integer',
                    is_float($value) => 'float',
                    is_array($value) => 'array',
                    is_object($value) => 'json',
                    default => 'string'
                }
            ];
        }

        // Tenta detectar e converter strings
        $trimmed = trim($value);

        // Detecta booleano
        if (strtolower($trimmed) === 'true' || strtolower($trimmed) === 'false') {
            return [
                'value' => strtolower($trimmed) === 'true',
                'type' => 'boolean'
            ];
        }

        // Detecta número inteiro
        if (preg_match('/^-?\d+$/', $trimmed)) {
            return [
                'value' => (int) $trimmed,
                'type' => 'integer'
            ];
        }

        // Detecta número decimal
        if (preg_match('/^-?\d*\.?\d+$/', $trimmed)) {
            return [
                'value' => (float) $trimmed,
                'type' => 'float'
            ];
        }

        // Detecta array/json
        if (($trimmed[0] ?? '') === '[' && ($trimmed[-1] ?? '') === ']' ||
            ($trimmed[0] ?? '') === '{' && ($trimmed[-1] ?? '') === '}'
        ) {
            try {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'value' => $decoded,
                        'type' => 'array'
                    ];
                }
            } catch (\Exception $e) {
                // Se falhar na decodificação, trata como string
            }
        }

        // Se nenhum tipo especial foi detectado, retorna como string
        return [
            'value' => $value,
            'type' => 'string'
        ];
    }
}
