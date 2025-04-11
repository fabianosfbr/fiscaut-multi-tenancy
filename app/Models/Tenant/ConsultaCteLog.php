<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ConsultaCteLog extends Model
{
    use HasUuids;

    protected $table = 'consulta_cte_logs';

    protected $fillable = [
        'organization_id',
        'sucesso',
        'mensagem',
        'detalhes',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'sucesso' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
