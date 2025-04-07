<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

class Registro1015
{
    public static function processar($xml)
    {
        $produtoText = '';

        $infAd = str_replace('|', '-', (string) $xml->NFe->infNFe->infAdic->infCpl);

        $produtoText = '|1010|'; // 1

        $produtoText .= '2' . "|"; // 2

        $produtoText .= $infAd . "|"; // 3

        return $produtoText . PHP_EOL;
    }
}
