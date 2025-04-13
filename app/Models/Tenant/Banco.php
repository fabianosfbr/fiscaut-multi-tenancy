<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    protected $guarded = ['id'];

    protected $table = 'contabil_bancos';


    public function plano_de_conta()
    {
        return $this->belongsTo(PlanoDeConta::class, 'conta_contabil', 'id');
    }
}
