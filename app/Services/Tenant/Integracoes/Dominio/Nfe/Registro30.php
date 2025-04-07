<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

class Registro30
{

    public static function processar($xml): string
    {
        $transp = $xml->NFe->infNFe->transp;

        $registro = '|0030|'; //1;
        $registro .= $transp->transporta->CNPJ ? $transp->transporta->CNPJ .  '|' : '' . '|'; //2
        $registro .= $transp->transporta->xNome ? $transp->transporta->xNome .  '|' : '' . '|'; //3
        $registro .= $transp->transporta->xEnder ? $transp->transporta->xEnder .  '|' : '' . '|'; //4
        $registro .= $transp->transporta->xMun ? $transp->transporta->xMun .  '|'  : '' . '|'; //5
        $registro .= $transp->transporta->UF ? $transp->transporta->UF .  '|' : '' . '|'; //6
        $registro .= $transp->transporta->IE ? $transp->transporta->IE .  '|' : '' . '|'; //7
        $registro .= $transp->transporta->tIE ? $transp->transporta->tIE .  '|' : '' . '|'; //8


        return  $registro . "|" . PHP_EOL;
    }

}
