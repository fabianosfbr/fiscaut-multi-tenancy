<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EntradasImpostosEquivalente extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status_icms' => 'boolean',
        'status_ipi' => 'boolean',
    ];
}
