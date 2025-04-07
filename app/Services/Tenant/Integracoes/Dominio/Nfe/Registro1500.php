<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use Illuminate\Support\Carbon;
use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro1500
{
    public static function processar($xml, $doc, $cfop, $cfopEquivalente, $percentual)
    {
        return self::gerarTextoPorParcela($xml, $doc,  $cfop, $cfopEquivalente, $percentual);
    }

    private static function gerarTextoPorParcela($xml, $doc,  $cfop, $cfopEquivalente, $percentual)
    {
        // Extrai os dados de cobrança
        $cobranca = self::extrairDadosCobranca($xml);

        $produtoText = '';

        $valoresTotais = [
            '1' => '|1500',
            '2' => '',
            '3' => '',
            '4' => '0,00',
            '5' => '0,00',
            '6' => '0,00',
            '7' => '0,00',
            '8' => '0,00',
            '9' => '0,00',
            '10' => '0,00',
            '11' => '0,00',
            '12' => '0,00',
            '13' => '0,00',
            '14' => '',
        ];

        if ($cfop != '5902' && isset($cobranca['duplicatas'])) {

            foreach ($cobranca['duplicatas'] as $duplicata) {

                $valoresTotais[2] = $duplicata['data_vencimento']->format('d/m/Y');
                $valoresTotais[3] = HelperFunctions::formatarDecimal($duplicata['valor'] * $percentual, 2);
                $valoresTotais[14] = $duplicata['numero'];


                $produtoText .= implode('|', $valoresTotais) . '|' . PHP_EOL;
            }
        }

        return $produtoText;
    }

    /**
     * Extrai os dados de cobrança da NF-e (fatura e duplicatas)
     * 
     * @return array|null Array com os dados de cobrança ou null se não houver
     */
    private static function extrairDadosCobranca($xml): ?array
    {
        // Verifica se existe o nó de cobrança
        if (!isset($xml->NFe->infNFe->cobr)) {
            return null;
        }

        $cobr = $xml->NFe->infNFe->cobr;
        $cobranca = [];

        // Extrai os dados da fatura
        if (isset($cobr->fat)) {
            $cobranca['fatura'] = [
                'numero' => (string) $cobr->fat->nFat,
                'valor_original' => (float) ($cobr->fat->vOrig ?? 0),
                'valor_desconto' => (float) ($cobr->fat->vDesc ?? 0),
                'valor_liquido' => (float) ($cobr->fat->vLiq ?? 0)
            ];
        }

        // Extrai as duplicatas
        if (isset($cobr->dup)) {
            $cobranca['duplicatas'] = [];

            foreach ($cobr->dup as $dup) {
                $duplicata = [
                    'numero' => (string) $dup->nDup,
                    'data_vencimento' => isset($dup->dVenc) ? Carbon::parse((string) $dup->dVenc) : null,
                    'valor' => (float) $dup->vDup
                ];

                $cobranca['duplicatas'][] = $duplicata;
            }
        }

        return $cobranca;
    }
}
