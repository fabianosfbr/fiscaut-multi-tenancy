<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro135
{

    public static function processar($doc, $produto)
    {
        $dataRegistro = null;

        if (is_null($doc->data_entrada)) {
            $dataRegistro = $doc->data_emissao;
        } else {
            $dataRegistro = $doc->data_entrada;
        }
        $dataRegistro = explode('/', $dataRegistro->format('d/m/Y'));

        $registro = '|0135|'; //1
        $registro .= '01/' . $dataRegistro[1] . '/' . $dataRegistro[2]   . "|"; //2
        $registro .= HelperFunctions::formatarDecimal($produto['valor_unitario'], 6) . "|"; //3

        return  $registro . "|" . PHP_EOL;
    }
}
