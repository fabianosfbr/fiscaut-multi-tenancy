<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaFiscalEletronica extends Model
{
    use HasUuids;

    protected $table = 'notas_fiscais_eletronica';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = ['id'];


    protected function casts(): array
    {
        return [
            'aut_xml' => 'array',
            'carta_correcao' => 'array',
        ];
    }


    public function produtos()
    {

        return $this->hasMany(Produto::class, 'nfe_id');
    }

}
