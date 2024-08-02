<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtiquetaPadrao extends Model
{
    protected $guarded = ['id'];

    protected $table = 'etiqueta_padrao';

    public $timestamps = false;
}
