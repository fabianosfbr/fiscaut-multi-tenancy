<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoEntradasProdutosGenerico extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
    ];

    public function produtos(): HasMany
    {
        return $this->hasMany(EntradasProdutosGenerico::class, 'grupo_id');
    }
}
