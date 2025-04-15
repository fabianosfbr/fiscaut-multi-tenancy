<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Cfop extends Model
{
    use HasFactory;

    protected $table = 'cfops';

    protected $fillable = [
        'codigo',
        'descricao',
    ];

    public static function getAllForTag()
    {

        return Cache::remember('cfop_for_tag_all', now()->addDay(), function () {
            return static::select(
                'codigo',
                DB::raw("CONCAT(cfops.codigo,'-',cfops.descricao) as full_name")
            )->get();
        });
    }
}
