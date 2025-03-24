<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifestacaoFiscal extends Model
{
    use HasUuids;

    protected $table = 'manifestacoes_fiscais';

    protected $fillable = [
        'organization_id',
        'documento_id',
        'documento_type',
        'chave_acesso',
        'tipo_documento',
        'tipo_manifestacao',
        'status',
        'protocolo',
        'justificativa',
        'data_manifestacao',
        'data_resposta',
        'erro',
        'xml_resposta',
        'created_by',
    ];

    protected $casts = [
        'data_manifestacao' => 'datetime',
        'data_resposta' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 