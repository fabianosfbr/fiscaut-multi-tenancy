<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtiquetaPadrao extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    protected $table = 'etiqueta_padrao';

    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo(CategoriaEtiquetaPadrao::class, 'category_id');
    }
}
