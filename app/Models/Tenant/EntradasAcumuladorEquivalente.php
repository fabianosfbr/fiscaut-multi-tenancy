<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EntradasAcumuladorEquivalente extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'valores' => 'json',
        'cfops' => 'json',
    ];
}
