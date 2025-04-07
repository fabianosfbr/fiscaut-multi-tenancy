<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

class Registro1010
{
    public static function processar($xml)
    {
        $produtoText = '';

        $infAdFisco = str_replace('|', '-', (string) $xml->NFe->infNFe->infAdic->infAdFisco);

        $produtoText = '|1015|'; // 1

        $produtoText .= '1' . "|"; // 2

        $produtoText .= $infAdFisco . "|"; // 3

        return $produtoText . PHP_EOL;
    }
}
