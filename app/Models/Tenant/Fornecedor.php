<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    protected $guarded = ['id'];

    protected $table = 'contabil_fornecedores';

    protected $casts = [
        'conta_contabil' => 'array',
        'descricao_conta_contabil' => 'array',
        'colunas_arquivo' => 'array',
    ];

    
    public function plano_de_conta()
    {
        return $this->belongsTo(PlanoDeConta::class, 'conta_contabil', 'id');
    }
}
