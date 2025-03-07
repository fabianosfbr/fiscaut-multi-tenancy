<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFiscalEletronicaItem extends Model
{
    use HasUuids;

    protected $table = 'nota_fiscal_eletronica_itens';

    protected $guarded = ['id'];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'valor_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'valor_seguro' => 'decimal:2',
        'valor_outras_despesas' => 'decimal:2',
        'base_calculo_icms' => 'decimal:2',
        'aliquota_icms' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'base_calculo_icms_st' => 'decimal:2',
        'aliquota_icms_st' => 'decimal:2',
        'valor_icms_st' => 'decimal:2',
        'base_calculo_ipi' => 'decimal:2',
        'aliquota_ipi' => 'decimal:2',
        'valor_ipi' => 'decimal:2',
        'base_calculo_pis' => 'decimal:2',
        'aliquota_pis' => 'decimal:2',
        'valor_pis' => 'decimal:2',
        'base_calculo_cofins' => 'decimal:2',
        'aliquota_cofins' => 'decimal:2',
        'valor_cofins' => 'decimal:2',
    ];

    public function notaFiscal(): BelongsTo
    {
        return $this->belongsTo(NotaFiscalEletronica::class, 'nfe_id');
    }

    // Método auxiliar para calcular o total de impostos do item
    public function getTotalImpostosAttribute(): float
    {
        return $this->valor_icms +
               $this->valor_icms_st +
               $this->valor_ipi +
               $this->valor_pis +
               $this->valor_cofins;
    }

    // Método auxiliar para calcular o valor total do item com impostos
    public function getValorTotalComImpostosAttribute(): float
    {
        return $this->valor_total + $this->total_impostos;
    }
}
