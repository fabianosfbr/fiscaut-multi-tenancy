<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ConhecimentoTransporteEletronicoHistorico extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $table = 'conhecimento_transporte_eletronico_historicos';

    protected $casts = [
        'data_alteracao' => 'datetime',
        'campos_alterados' => 'array',
    ];

    public function conhecimentoTransporte()
    {
        return $this->belongsTo(ConhecimentoTransporteEletronico::class, 'cte_id');
    }
    
    
}
