<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];

    public function nota_fiscal()
    {
        return $this->belongsTo(NotaFiscalEletronica::class, 'nfe_id');
    }
}
