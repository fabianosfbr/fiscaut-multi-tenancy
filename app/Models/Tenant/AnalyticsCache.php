<?php

namespace App\Models\Tenant;


use Illuminate\Database\Eloquent\Model;

class AnalyticsCache extends Model
{
  
    protected $table = 'analytics_caches';    

    protected $fillable = ['key', 'value', 'generated_at'];

    /**
     * Converte o valor armazenado de JSON para array
     */
    public function getValueAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Converte o valor para JSON antes de armazenar
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = json_encode($value);
    }

    /**
     * Armazena ou atualiza dados de cache
     */
    public static function store(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Obtém dados do cache, retornando null se não existir
     * ou se for mais antigo que $maxAgeInSeconds
     */
    public static function retrieve(string $key, int $maxAgeInSeconds = 7200)
    {
        $cached = self::where('key', $key)
            ->where('generated_at', '>=', now()->subSeconds($maxAgeInSeconds))
            ->first();

        return $cached ? $cached->value : null;
    }
}
