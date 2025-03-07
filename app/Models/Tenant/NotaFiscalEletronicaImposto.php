<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFiscalEletronicaImposto extends Model
{
    use HasUuids;

    protected $table = 'nota_fiscal_eletronica_impostos';

    protected $guarded = ['id'];

    protected $casts = [
        'base_calculo_icms' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'valor_icms_desonerado' => 'decimal:2',
        'valor_icms_fcp' => 'decimal:2',
        'base_calculo_icms_st' => 'decimal:2',
        'valor_icms_st' => 'decimal:2',
        'valor_icms_st_fcp' => 'decimal:2',
        'base_calculo_ipi' => 'decimal:2',
        'valor_ipi' => 'decimal:2',
        'base_calculo_pis' => 'decimal:2',
        'valor_pis' => 'decimal:2',
        'base_calculo_cofins' => 'decimal:2',
        'valor_cofins' => 'decimal:2',
        'valor_aproximado_tributos' => 'decimal:2',
        'valor_ii' => 'decimal:2',
        'valor_issqn' => 'decimal:2',
        'valor_total_tributos' => 'decimal:2',
    ];

    public function notaFiscal(): BelongsTo
    {
        return $this->belongsTo(NotaFiscalEletronica::class, 'nfe_id');
    }

    // Método auxiliar para calcular o total de impostos federais
    public function getTotalImpostosFederaisAttribute(): float
    {
        return $this->valor_ipi +
               $this->valor_pis +
               $this->valor_cofins +
               $this->valor_ii;
    }

    // Método auxiliar para calcular o total de impostos estaduais
    public function getTotalImpostosEstaduaisAttribute(): float
    {
        return $this->valor_icms +
               $this->valor_icms_st +
               $this->valor_icms_fcp +
               $this->valor_icms_st_fcp;
    }
}
