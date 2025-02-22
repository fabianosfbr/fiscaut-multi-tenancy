<?php

namespace App\Models\Tenant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cfop extends Model
{
    use HasFactory;


    public static function getAllForTag()
    {

        return Cache::remember("cfop_for_tag_all", now()->addDay(), function () {
            return static::select(
                'codigo',
                DB::raw("CONCAT(cfops.codigo,'-',cfops.descricao) as full_name")
            )->get();
        });
    }
}
