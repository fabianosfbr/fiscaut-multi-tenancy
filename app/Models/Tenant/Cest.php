<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Cest extends Model
{
    protected $table = 'tabela_cest';

    public $timestamps = false;

    protected $casts = [
        'ncm' => 'string',
        'cest' => 'string',
    ];
}
