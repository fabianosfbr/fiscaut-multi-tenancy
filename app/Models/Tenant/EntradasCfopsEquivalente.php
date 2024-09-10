<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradasCfopsEquivalente extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'cfop_entrada' => 'integer',
        'valores' => 'array',
    ];
}
