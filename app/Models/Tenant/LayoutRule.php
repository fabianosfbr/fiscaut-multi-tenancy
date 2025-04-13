<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\TipoFonteDeDadosEnum;
use App\Enums\Tenant\TipoRegraExportacaoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LayoutRule extends Model
{
    protected $table = 'contabil_layout_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'data_source_parametros_gerais_target_columns' => 'array',
        'data_source_historical_columns' => 'array',
        'rule_type' => TipoRegraExportacaoEnum::class,
        'data_source_type' => TipoFonteDeDadosEnum::class,
    ];


    public function layout()
    {
        return $this->belongsTo(Layout::class);
    }
}
