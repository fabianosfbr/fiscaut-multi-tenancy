<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

class Registro150
{
    public static function processar($produto): string
    {

        $registro = '|0150|'; //1
        $registro .= $produto['unidade']  . "|"; //2
        $registro .= $produto['unidade'] . "|"; //3

        return  $registro  . PHP_EOL;
    }

}
