<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProdutoFornecedor extends Model
{
    use HasUuids;

    protected $table = 'produto_fornecedores';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

}
