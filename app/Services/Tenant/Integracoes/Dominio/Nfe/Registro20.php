<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro20
{

    public static function processar($element, $doc, $currentIssuer): string
    {
        $produtoText = '';

        $produtos = HelperFunctions::processaProduto($element);

        $cfops = HelperFunctions::uniqueCfops($produtos);

        $isExportacao = HelperFunctions::checkExportacao($cfops);
        $isEntradaPropria = HelperFunctions::checkEntradaPropria($doc, $currentIssuer);


        if ($isEntradaPropria && !$isExportacao) {
            $produtoText = HelperFunctions::processaEntradaPropria($element);
        } elseif ($isEntradaPropria && $isExportacao) {
            $produtoText = HelperFunctions::processaEntradaPropriaExportacao($element);
        } else {
            $produtoText = HelperFunctions::processaEntradaTerceiro($element);
        }
        
        return $produtoText;
    }
}
