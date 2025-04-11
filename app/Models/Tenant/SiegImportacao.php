<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SiegImportacao extends Model
{
    protected $table = 'sieg_importacoes';

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sucesso' => 'boolean',
    ];
    
}
