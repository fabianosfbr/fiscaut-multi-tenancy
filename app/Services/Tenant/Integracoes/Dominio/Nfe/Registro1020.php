<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro1020
{
    public static function processar($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $percentualTagged, $isZeraIcms, $isZeraIpi)
    {
        $produtoText = '';

        $tageeds = $doc->tagged()->get()->toArray();

        $category = count($tageeds) > 0 ? $tageeds[0]['tag']['category'] : null;

        $isDifal = false;
        if ($category && $category['is_enable'] == 1) {
            $isDifal = $category['is_difal'] ?? false;
        }


        $produtoText .=  self::gerarLinhaICMS($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $percentualTagged, $isZeraIcms);

        $produtoText .=  self::gerarLinhaIpi($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $percentualTagged, $isZeraIpi);

        $produtoText .=  self::gerarLinhaDifal($doc, $isDifal);

        return $produtoText;
    }

    private static function gerarLinhaICMS($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $percentual, $isZeraIcms): string
    {
        $produtoText = '';

        $valoresTotais = [
            '1' => '|1020',
            '2' => '1',
            '3' => '0,00',
            '4' => 0,
            '5' => 0,
            '6' => 0,
            '7' => 0,
            '8' => 0,
            '9' => 0,
            '10' => 0,
            '11' => 0,
            '12' => '',
            '13' => '0,00',
            '14' => '0,00',
            '15' => '',
            '16' => '',
            '17' => '',
        ];

        foreach ($valoresTotaisSegmentadoPorCfop as $cfopSegmento => $valores) {


            if ($cfopSegmento == $cfop) {

                $valorIPI = $valores['vIPI'] * $percentual;
                $valorST = $valores['vST']  * $percentual;
                $valorContabil = ($valores['vContabil'] - $valores['vDesc'])  * $percentual;

                if (strlen($valores['csosn']) > 0 and !$isZeraIcms) {
                    $valoresTotais[8] += $valores['vProd'] * $percentual;
                    $valoresTotais[11] += $valores['vProd'] * $percentual;
                } elseif ($isZeraIcms) {

                    $valoresTotais[8] += $valorContabil - ($valorIPI + $valorST);
                    $valoresTotais[9] += $valorIPI;
                    $valoresTotais[10] += $valorST;
                    $valoresTotais[11] += $valorContabil;
                } else {

                    if ($valores['CST'] == '00') {

                        $valoresTotais[4] += $valores['vBcICMS']  * $percentual;
                        $valoresTotais[5] += $valores['pICMS'];
                        $valoresTotais[6] += $valores['vICMS'] * $percentual;

                        $valoresTotais[7] += 0;
                        $valoresTotais[8] += $valorContabil - ($valorIPI + $valorST + $valores['vBcICMS'] * $percentual);
                        $valoresTotais[9] += $valorIPI * $percentual;
                        $valoresTotais[10] += $valorST  * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }


                    if ($valores['CST'] == '10') {


                        $opcaoCreditoIcms = HelperFunctions::validaOpcaoCreditoIcms($valores, $doc, $currentIssuer);

                        $valoresTotais[4] += $opcaoCreditoIcms ? $valores['vBcICMS'] * $percentual  : 0;
                        $valoresTotais[5] += $opcaoCreditoIcms ? $valores['pICMS'] : 0;
                        $valoresTotais[6] += $opcaoCreditoIcms ? $valores['vICMS'] * $percentual : 0;

                        $valoresTotais[8] += $opcaoCreditoIcms ? $valorContabil - ($valorIPI + $valores['vBcICMS'] * $percentual + $valores['vICMSST'] * $percentual)  : $valorContabil - ($valorIPI + $valores['vICMSST']);
                        $valoresTotais[9] +=  $valores['vIPI'] * $percentual;
                        $valoresTotais[10] += $valores['vICMSST'] * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }

                    if ($valores['CST'] == '20') {

                        $valoresTotais[4] += $valores['vBcICMS'] * $percentual;
                        $valoresTotais[5] += $valores['pICMS'];
                        $valoresTotais[6] += $valores['vICMS'] * $percentual;
                        $valoresTotais[7] += $valorContabil - ($valores['vBcICMS'] * $percentual + $valores['vIPI'] * $percentual + $valores['vST']  * $percentual);
                        $valoresTotais[8] += 0;
                        $valoresTotais[9] += $valores['vIPI'] * $percentual;
                        $valoresTotais[10] += $valores['vST']  * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }

                    if ($valores['CST'] == '30' || $valores['CST'] == '70') {

                        $opcaoCreditoIcms = HelperFunctions::validaOpcaoCreditoIcms($valores, $doc, $currentIssuer);

                        $valoresTotais[4] += $opcaoCreditoIcms ? $valores['vBcICMS'] * $percentual  : 0;
                        $valoresTotais[5] += $opcaoCreditoIcms ? $valores['pICMS'] : 0;
                        $valoresTotais[6] += $opcaoCreditoIcms ? $valores['vICMS'] * $percentual : 0;

                        $valoresTotais[7] += $opcaoCreditoIcms ? $valorContabil - ($valorIPI + $valores['vBcICMS'] * $percentual + $valores['vICMSST'] * $percentual)  : $valorContabil - ($valorIPI + $valores['vICMSST']);
                        $valoresTotais[9] +=  $valores['vIPI'] * $percentual;
                        $valoresTotais[10] += $valores['vICMSST'] * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }

                    if ($valores['CST'] == '40' || $valores['CST'] == '41') {

                        $valoresTotais[4] += $valores['vBcICMS'] * $percentual;
                        $valoresTotais[5] = $valores['pICMS'];
                        $valoresTotais[6] += $valores['vICMS'] * $percentual;
                        $valoresTotais[7] += $valorContabil - ($valorIPI + $valores['vBcICMS'] * $percentual + $valores['vICMSST'] * $percentual);
                        $valoresTotais[8] += 0;
                        $valoresTotais[9] += $valores['vIPI']  * $percentual;
                        $valoresTotais[10] += $valores['vICMSST']  * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }

                    if ($valores['CST'] == '50' || $valores['CST'] == '51') {

                        $valoresTotais[4] += $valores['vBcICMS'] * $percentual;
                        $valoresTotais[5] = $valores['pICMS'];
                        $valoresTotais[6] += $valores['vICMS'] * $percentual;
                        $valoresTotais[7] += 0;
                        $valoresTotais[8] += $valorContabil - ($valorIPI + $valores['vBcICMS'] * $percentual + $valores['vICMSST'] * $percentual);
                        $valoresTotais[9] += $valores['vIPI']  * $percentual;
                        $valoresTotais[10] += $valores['vICMSST']  * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }


                    if ($valores['CST'] == '60' || $valores['CST'] == '61' || $valores['CST'] == '90') {

                        $valoresTotais[8] += $valorContabil - ($valorIPI + $valorST);
                        $valoresTotais[9] += $valores['vIPI']  * $percentual;
                        $valoresTotais[10] += $valores['vST']  * $percentual;
                        $valoresTotais[11] += $valorContabil;
                    }
                }
            }
        }

        $valoresTotais[4] = HelperFunctions::formatarDecimal($valoresTotais[4], 2);
        $valoresTotais[5] = HelperFunctions::formatarDecimal($valoresTotais[5], 2);
        $valoresTotais[6] = HelperFunctions::formatarDecimal($valoresTotais[6], 2);
        $valoresTotais[7] = HelperFunctions::formatarDecimal($valoresTotais[7], 2);
        $valoresTotais[8] = HelperFunctions::formatarDecimal($valoresTotais[8], 2);
        $valoresTotais[9] = HelperFunctions::formatarDecimal($valoresTotais[9], 2);
        $valoresTotais[10] = HelperFunctions::formatarDecimal($valoresTotais[10], 2);
        $valoresTotais[11] = HelperFunctions::formatarDecimal($valoresTotais[11], 2);


        $produtoText = implode('|', $valoresTotais);

        return $produtoText . PHP_EOL;
    }

    private static function gerarLinhaIpi($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $percentual, $isZeraIpi): string
    {

        $isIndustria = HelperFunctions::checkIssuerType('industria', $currentIssuer);


        $produtoText = '';

        $valoresTotais = [
            '1' => '|1020',
            '2' => '2',
            '3' => '0,00',
            '4' => 0,
            '5' => 0,
            '6' => 0,
            '7' => 0,
            '8' => 0,
            '9' => 0,
            '10' => 0,
            '11' => 0,
            '12' => '',
            '13' => '0,00',
            '14' => '0,00',
            '15' => '',
            '16' => '',
            '17' => '',
        ];

        if ($isIndustria) {

            foreach ($valoresTotaisSegmentadoPorCfop as $cfopSegmento => $valores) {
                if ($cfop == $cfopSegmento) {

                    $valorIPI = $valores['vIPI'] * $percentual;
                    $valorContabil = ($valores['vContabil'] - $valores['vDesc']) * $percentual;

                    if (is_null($valores['CST']) and !is_null($valores['CSOSN'])) {

                        $valoresTotais[8] += $valores['vProd'] * $percentual;
                        $valoresTotais[11] += $valores['vProd'] * $percentual;
                    }


                    if ($isZeraIpi || is_null($valores['CSTIPI'])) {
                        $valoresTotais[4] += 0;
                        $valoresTotais[5] += 0;
                        $valoresTotais[6] +=  0;
                        $valoresTotais[7] += 0;
                        $valoresTotais[8] = $valorContabil;
                        $valoresTotais[9] += 0;
                        $valoresTotais[10] += 0;
                        $valoresTotais[11] = $valorContabil;
                    } else {

                        if ($valores['CSTIPI'] == '50' || $valores['CSTIPI'] == '51' || $valores['CST'] == 'CSTIPI' || $valores['CSTIPI'] == '54' || $valores['CSTIPI'] == '55' || $valores['CSTIPI'] == '99') {


                            if ($valores['vIPI'] == 0 and $valores['pIPI'] == 0 and $valores['vBcIPI'] > 0) {

                                $valoresTotais[8] = $valorContabil;

                                $valoresTotais[11] = $valorContabil;
                            } else {

                                $valoresTotais[4] += $valores['vBcIPI'];
                                $valoresTotais[5] += $valores['pIPI'];
                                $valoresTotais[6] =  $valorIPI;
                                $valoresTotais[7] += 0;
                                $valoresTotais[8] = $valorContabil - ($valores['vBcIPI'] + $valorIPI);
                                $valoresTotais[9] += 0;
                                $valoresTotais[10] += 0;
                                $valoresTotais[11] = $valorContabil;
                            }
                        }


                        if ($valores['CSTIPI'] == '52' || $valores['CSTIPI'] == '53') {

                            $valoresTotais[4] += $valores['vBcIPI'];
                            $valoresTotais[5] += $valores['pIPI'];
                            $valoresTotais[6] =  $valorIPI;
                            $valoresTotais[7] = $valorContabil - ($valores['vBcIPI'] + $valorIPI);
                            $valoresTotais[8] += 0;
                            $valoresTotais[9] += 0;
                            $valoresTotais[10] += 0;
                            $valoresTotais[11] = $valorContabil;
                        }
                    }
                }
            }
            $valoresTotais[4] = HelperFunctions::formatarDecimal($valoresTotais[4], 2);
            $valoresTotais[5] = HelperFunctions::formatarDecimal($valoresTotais[5], 2);
            $valoresTotais[6] = HelperFunctions::formatarDecimal($valoresTotais[6], 2);
            $valoresTotais[7] = HelperFunctions::formatarDecimal($valoresTotais[7], 2);
            $valoresTotais[8] = HelperFunctions::formatarDecimal($valoresTotais[8], 2);
            $valoresTotais[9] = HelperFunctions::formatarDecimal($valoresTotais[9], 2);
            $valoresTotais[10] = HelperFunctions::formatarDecimal($valoresTotais[10], 2);
            $valoresTotais[11] = HelperFunctions::formatarDecimal($valoresTotais[11], 2);


            $produtoText = implode('|', $valoresTotais) . PHP_EOL;
        }


        return $produtoText;
    }

    private static function gerarLinhaDifal($doc, $isDifal): string
    {
        $produtoText = '';

        $valoresTotais = [
            '1' => '|1020',
            '2' => '8',
            '3' => '0,00',
            '4' => 0,
            '5' => 0,
            '6' => 0,
            '7' => 0,
            '8' => 0,
            '9' => 0,
            '10' => 0,
            '11' => 0,
            '12' => '',
            '13' => '0,00',
            '14' => '0,00',
            '15' => '',
            '16' => '',
            '17' => '',
            '18' => '',
        ];

        if ($doc->possuiDifal() and  $isDifal) {

            foreach ($doc->calcularDifalProdutos() as $valores) {

                $valoresTotais[4] += floatval(str_replace(',', '', $valores['base_calculo']));
                $valoresTotais[5] = floatval(str_replace(',', '', $valores['aliquota_destino']));
                $valoresTotais[6] += floatval(str_replace(',', '', $valores['valor_difal']));
                $valoresTotais[7] += 0;
                $valoresTotais[8] += 0;
                $valoresTotais[9] += 0;
                $valoresTotais[10] += 0;
                $valoresTotais[11] += floatval(str_replace(',', '', $valores['valor_contabil'] - $valores['valor_desconto']));
                $valoresTotais[15] = floatval(str_replace(',', '', $valores['aliquota_origem']));
            }


            $valoresTotais[4] = HelperFunctions::formatarDecimal($valoresTotais[4], 2);
            $valoresTotais[5] = HelperFunctions::formatarDecimal($valoresTotais[5], 2);
            $valoresTotais[6] = HelperFunctions::formatarDecimal($valoresTotais[6], 2);
            $valoresTotais[7] = HelperFunctions::formatarDecimal($valoresTotais[7], 2);
            $valoresTotais[8] = HelperFunctions::formatarDecimal($valoresTotais[8], 2);
            $valoresTotais[9] = HelperFunctions::formatarDecimal($valoresTotais[9], 2);
            $valoresTotais[10] = HelperFunctions::formatarDecimal($valoresTotais[10], 2);
            $valoresTotais[11] = HelperFunctions::formatarDecimal($valoresTotais[11], 2);
            $valoresTotais[15] = HelperFunctions::formatarDecimal($valoresTotais[15], 2);


            $produtoText = implode('|', $valoresTotais) . PHP_EOL;
        }

        return $produtoText;
    }
}
