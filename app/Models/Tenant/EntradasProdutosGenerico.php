<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class EntradasProdutosGenerico extends Model
{
    protected $guarded = ['id'];

    protected function ncm(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => sprintf('%08d', $value),
        );
    }

    public function grupo()
    {
        return $this->belongsTo(GrupoEntradasProdutosGenerico::class, 'grupo_id');
    }
}
