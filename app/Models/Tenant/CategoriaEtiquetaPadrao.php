<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Concerns\Tenantable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoriaEtiquetaPadrao extends Model
{
    use HasFactory, HasUuids, Tenantable;

    protected $guarded = ['id'];

    protected $table = 'categoria_etiqueta_padrao';

    public $timestamps = false;



    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tags()
    {
        return $this->hasMany(EtiquetaPadrao::class, 'category_id');
    }
}
