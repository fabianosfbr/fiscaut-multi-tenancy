<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $guarded = ['id'];

    protected $table = 'contabil_clientes';

    protected $casts = [
        'conta_contabil' => 'array',
        'descricao_conta_contabil' => 'array',
        'colunas_arquivo' => 'array',
    ];

    //Plano de contas
    public function plano_de_conta()
    {
        return $this->belongsTo(PlanoDeConta::class, 'conta_contabil', 'id');
    }
}
