<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoNfe extends Model
{
    use HasUuids;

    protected $table = 'eventos_nfe';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'chave_nfe',
        'tipo_evento',
        'numero_sequencial',
        'data_evento',
        'protocolo',
        'status_sefaz',
        'motivo',
        'xml_evento',
        'xml_resumo'
    ];

    protected $casts = [
        'data_evento' => 'datetime',
        'numero_sequencial' => 'integer'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function notaFiscal(): BelongsTo
    {
        return $this->belongsTo(NotaFiscalEletronica::class, 'chave_nfe', 'chave');
    }

    /**
     * Retorna a descrição do tipo de evento
     */
    public function getDescricaoEvento(): string
    {
        return match($this->tipo_evento) {
            '210200' => 'Confirmação da Operação',
            '210210' => 'Ciência da Operação',
            '210220' => 'Desconhecimento da Operação',
            '210240' => 'Operação não Realizada',
            default => 'Evento Desconhecido'
        };
    }
} 