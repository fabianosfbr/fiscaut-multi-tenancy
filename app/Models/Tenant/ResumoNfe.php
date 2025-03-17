<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumoNfe extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'resumo_nfes';
    
    protected $fillable = [
        'organization_id',
        'chave',
        'cnpj_emitente',
        'nome_emitente',
        'ie_emitente',
        'data_emissao',
        'valor_total',
        'situacao',
        'xml_resumo',
        'necessita_manifestacao'
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'valor_total' => 'decimal:2',
        'necessita_manifestacao' => 'boolean'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
} 