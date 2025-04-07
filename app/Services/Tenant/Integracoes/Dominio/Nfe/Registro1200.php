<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro1200
{
    public static function processar($valoresTotaisSegmentadoPorCfop, $isZeraIcms)
    {
        $produtoText = '';

        if($isZeraIcms){
            return $produtoText;
        }

        foreach ($valoresTotaisSegmentadoPorCfop as $key => $item) {

            $produtoText .= '|1200|' .
            HelperFunctions::formatarDecimal($item['vProd'], 2) . '|' .
            HelperFunctions::formatarDecimal($item['pCredSN'], 2) . '|' .
            HelperFunctions::formatarDecimal($item['vCredICMSSN'], 2) . '|' . PHP_EOL;
        }

        return $produtoText;
    }
}
