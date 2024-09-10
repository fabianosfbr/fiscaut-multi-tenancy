<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrupoEntradasCfopsEquivalente extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'json',
    ];

    public function cfops(): HasMany
    {
        return $this->hasMany(EntradasCfopsEquivalente::class, 'grupo_id');
    }
}
