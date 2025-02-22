<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EntradasCfopsEquivalente extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'cfop_entrada' => 'integer',
        'valores' => 'array',
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoEntradasCfopsEquivalente::class, 'grupo_id');
    }
}
