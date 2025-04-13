<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoContabil extends Model
{
    protected $guarded = ['id'];

    protected $table = 'contabil_historico_contabeis';
}
