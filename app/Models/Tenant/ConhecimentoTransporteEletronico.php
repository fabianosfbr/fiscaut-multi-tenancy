<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\OrigemCteEnum;
use App\Enums\Tenant\StatusCteEnum;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Tenant\StatusManifestoCteEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ConhecimentoTransporteEletronico extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $table = 'conhecimentos_transportes_eletronico';

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_entrada' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_receber' => 'decimal:2',
        'valor_servico' => 'decimal:2',
        'valor_icms' => 'decimal:2',
        'base_calculo_icms' => 'decimal:2',
        'aliquota_icms' => 'decimal:2',
        'peso_bruto' => 'decimal:3',
        'peso_base_calculo' => 'decimal:3',
        'peso_aferido' => 'decimal:3',
        'status_cte' => StatusCteEnum::class,
        'status_manifestacao' => StatusManifestoCteEnum::class,
        'origem' => OrigemCteEnum::class,
    ];

    public function historicos()
    {
        return $this->hasMany(ConhecimentoTransporteEletronicoHistorico::class, 'cte_id');
    }

    
}
