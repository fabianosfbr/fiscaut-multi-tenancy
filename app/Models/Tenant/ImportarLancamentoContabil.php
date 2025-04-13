<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportarLancamentoContabil extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $table = 'contabil_importar_lancamentos_contabeis';

    protected $casts = [
        'metadata' => 'array',
        'data' => 'date',
    ];
}
