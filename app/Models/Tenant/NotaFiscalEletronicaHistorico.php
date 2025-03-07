<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotaFiscalEletronicaHistorico extends Model
{
    use HasUuids;

    protected $table = 'nota_fiscal_eletronica_historicos';

    protected $guarded = ['id'];

    protected $casts = [
        'data_alteracao' => 'datetime',
        'campos_alterados' => 'array',
    ];

    public function notaFiscal()
    {
        return $this->belongsTo(NotaFiscalEletronica::class, 'nfe_id');
    } //
}
