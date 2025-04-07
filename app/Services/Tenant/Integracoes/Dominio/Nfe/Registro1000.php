<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro1000
{
    public static function processar($xml, $doc, $currentIssuer)
    {
        $tageeds = $doc->tagged()->get()->toArray();

        $valoresTotais = HelperFunctions::extrairValoresTotaisNota($xml);

        $valoresPorProduto = HelperFunctions::processaProduto($xml);

        $valoresTotaisSegmentadoPorCfop = HelperFunctions::calcularValorSegmentadoPorCfop($valoresPorProduto);

        $isZeraIcms = HelperFunctions::isZeraIcms($tageeds, $currentIssuer);

        $isZeraIpi = HelperFunctions::isZeraIpi($tageeds, $currentIssuer);

        $tageeds = HelperFunctions::calcularPercentualEtiqueta($tageeds, $valoresTotais);


        return self::gerarTextoProdutos($tageeds, $valoresTotaisSegmentadoPorCfop, $valoresPorProduto, $xml, $doc, $currentIssuer, $isZeraIcms, $isZeraIpi);
    }

    private static function gerarTextoProdutos($tageeds,  $valoresTotaisSegmentadoPorCfop, $valoresPorProduto, $xml, $doc, $currentIssuer, $isZeraIcms, $isZeraIpi)
    {
        $produtoText = '';

        $infAdFisco = str_replace('|', '-', (string) $xml->NFe->infNFe->infAdic->infAdFisco) ;

        $numSegmento = count($tageeds) > 1 ? 1 : 0;


        foreach ($valoresTotaisSegmentadoPorCfop as $cfop => $valoresSegmento) {

            if (count($tageeds) > 0) {

                foreach ($tageeds as $tageed) {

                    $produtoText .= self::gerarLinhaProduto($tageed, $valoresTotaisSegmentadoPorCfop,  $valoresPorProduto, $cfop, $xml, $valoresSegmento, $doc, $infAdFisco, $numSegmento, $currentIssuer, $isZeraIcms, $isZeraIpi);
                    
                    $numSegmento = $numSegmento + 1;
                }
            }
        }

        return $produtoText;
    }

    private static function gerarLinhaProduto($tageed, $valoresTotaisSegmentadoPorCfop,  $valoresPorProduto, $cfop, $xml, $valoresSegmento, $doc, $infAdFisco, $numSegmento, $currentIssuer, $isZeraIcms, $isZeraIpi)
    {
        $produtoText = '';

        $isIndustria = HelperFunctions::checkIssuerType('industria', $currentIssuer);

        $cfopEquivalente = count($tageed) > 0 ? HelperFunctions::getCfopEquivalente($tageed['tag'], $cfop, $currentIssuer, $doc) : $cfop;

        $acumulador = count($tageed) > 0 ? HelperFunctions::getAcumulador($tageed['tag'], $cfopEquivalente, $currentIssuer, $doc) : '';

        isset($tageed['percentual']) ? $tageed['percentual'] : $tageed['percentual'] = 1;

        $isExportacao = HelperFunctions::checkExportacao([$cfop]);

        $isIPIDevolucao = isset($tageed['tag']) ? boolval($tageed['tag']['category']['is_devolucao']) : false;

        // dd((string)$xml->NFe->infNFe->dest->IM);
        $modFrete = (int) $xml->NFe->infNFe->transp->modFrete;

        //dd($valoresPorProduto);
        //  dd($valoresSegmento);
        $produtoText .= '|1000|'; //1
        $produtoText .=  '36' . "|"; //2 vide config(doc-especies.php)
        $produtoText .= $isExportacao ? '00000000000000' . '|' : $doc->cnpj_emitente . "|"; //3
        $produtoText .=  '0' . "|"; //4
        $produtoText .=  $acumulador . "|"; //5
        $produtoText .= $cfopEquivalente . "|"; //6
        $produtoText .=  $numSegmento . "|"; //7
        $produtoText .=  $doc->numero . "|"; //8
        $produtoText .=  $doc->serie . "|"; //9
        $produtoText .=  '0' . "|"; //10
        $produtoText .=  isset($doc->data_entrada) ? $doc->data_entrada->format('d/m/Y') . "|" : '' . "|"; //11
        $produtoText .=  $doc->data_emissao->format('d/m/Y') . "|"; //12
        $produtoText .=   HelperFunctions::formatarDecimal(($valoresSegmento['vContabil'] - $valoresSegmento['vDesc']) * $tageed['percentual'], 2) . "|";  //13
        $produtoText .=  '' . "|"; //14
        $produtoText .=   $infAdFisco  . "|"; //15
        $produtoText .=  HelperFunctions::checkTipoFrete($modFrete) . "|"; //16
        $produtoText .=  $doc->tipo == '1' ? 'T' : 'P' . "|"; //17
        $produtoText .=  '0' . "|"; //18
        $produtoText .=  '0' . "|"; //19
        $produtoText .=  '' . "|"; //20
        $produtoText .=  '' . "|"; //21
        $produtoText .=  '' . "|"; //22
        $produtoText .=  $doc->data_emissao->format('d/m/Y') . "|";  //23
        $produtoText .=  'E' . "|"; //24
        $produtoText .=  'E' . "|"; //25
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vFrete'] * $tageed['percentual'], 2) . "|"; //26
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vSeg'] * $tageed['percentual'], 2) . "|"; //27
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vOutro'] * $tageed['percentual'], 2) . "|"; //28
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vPIS'] * $tageed['percentual'], 2) . "|"; //29
        $produtoText .=  '' . "|"; //30
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vCOFINS'] * $tageed['percentual'], 2) . "|"; //31
        $produtoText .=  '' . "|"; //32
        $produtoText .=  '' . "|"; //33
        $produtoText .=  '' . "|"; //34
        $produtoText .=  '0,00' . "|"; //35
        $produtoText .=  '0,00' . "|"; //36
        $produtoText .=  '0,00' . "|"; //37
        $produtoText .=  '' . "|"; //38
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vProd'] * $tageed['percentual'], 2) . "|"; //39
        $produtoText .=  '0' . "|";  //40
        $produtoText .=  '0' . "|"; //41
        $produtoText .=  '00' . "|"; //42
        $produtoText .=  '0' . "|"; //43
        $produtoText .=  $isExportacao ? $doc->ie_destinatario . '|' : $doc->ie_emitente . '|'; //44
        $produtoText .=  $isExportacao ? (string)$xml->NFe->infNFe->dest->IM . '|' : (string)$xml->NFe->infNFe->emit->IM . '|';  //45
        $produtoText .=  '' . "|"; //46
        $produtoText .=  '0,00' . "|"; //47
        $produtoText .=  $doc->data_emissao->format('d/m/Y') . "|";  //48
        $produtoText .=  '' . "|"; //49
        $produtoText .=  '' . "|"; //50
        $produtoText .=  isset($doc->data_entrada) ? $doc->data_entrada->format('d/m/Y') . "|" : '' . "|"; //51
        $produtoText .=  '' . "|"; //52
        $produtoText .=  'N' . "|"; //53
        $produtoText .=  $doc->chave_acesso . "|"; //54
        $produtoText .=  '' . "|"; //55
        $produtoText .=  '' . "|"; //56
        $produtoText .=  $cfop . "|"; //57
        $produtoText .=  '' . "|"; //58
        $produtoText .=  '' . "|"; //59
        $produtoText .=  '' . "|"; //60
        $produtoText .=  '' . "|"; //61
        $produtoText .=  '' . "|"; //62
        $produtoText .=  '' . "|"; //63
        $produtoText .=  '' . "|"; //64
        $produtoText .=  '' . "|"; //65
        $produtoText .=  '' . "|"; //66
        $produtoText .=  '' . "|"; //67
        $produtoText .=  '' . "|"; //68
        $produtoText .=  '' . "|"; //69
        $produtoText .=  '' . "|"; //70
        $produtoText .=  '' . "|"; //71
        $produtoText .=  '' . "|"; //72
        $produtoText .=  '' . "|"; //73
        $produtoText .=  '' . "|"; //74
        $produtoText .=  '' . "|"; //75
        $produtoText .=  '' . "|"; //76
        $produtoText .=  '' . "|"; //77
        $produtoText .=  '' . "|"; //78
        $produtoText .=  '' . "|"; //79
        $produtoText .=  '' . "|"; //80
        $produtoText .=  '' . "|"; //81
        $produtoText .=  isset($doc->data_entrada) ? $doc->data_entrada->format('d/m/Y') . "|" : '' . "|"; //82
        $produtoText .=  '' . "|"; //83
        $produtoText .=  '' . "|"; //84
        $produtoText .=  HelperFunctions::ajustaIpi($valoresSegmento['vIPI'], $tageed, $currentIssuer, $isIndustria) . "|"; //85
        $produtoText .=  '' . "|"; //86
        $produtoText .=  '' . "|"; //87
        $produtoText .=  '' . "|"; //88
        $produtoText .=  '' . "|"; //89
        $produtoText .=  !$isIPIDevolucao ? HelperFunctions::formatarDecimal($valoresSegmento['vIPI'] * $tageed['percentual'], 2)  . "|" :  "0,00"  . "|"; //90
        $produtoText .=  HelperFunctions::formatarDecimal($valoresSegmento['vST'] ?? 0 * $tageed['percentual'], 2) . "|"; //91
        $produtoText .=  '' . "|"; //92
        $produtoText .=  '' . "|"; //93
        $produtoText .=  '' . "|"; //94
        $produtoText .=  '' . "|"; //95
        $produtoText .=  '' . "|"; //96
        $produtoText .=  HelperFunctions::formatarDecimal($valoresPorProduto['valor_icms_deson'] ?? 0 * $tageed['percentual']) . "|"; //97
        $produtoText .=  $isIPIDevolucao ? HelperFunctions::formatarDecimal($valoresSegmento['vIPI'], 2) .  "|" . PHP_EOL : '' . "|" . PHP_EOL; //98


        $produtoText .= Registro1010::processar($xml);
        $produtoText .= Registro1015::processar($xml);
        $produtoText .= Registro1020::processar($doc, $currentIssuer, $cfop, $valoresTotaisSegmentadoPorCfop, $tageed['percentual'], $isZeraIcms, $isZeraIpi);
        $produtoText .= Registro1030::processar($doc, $valoresPorProduto, $tageed['percentual'], $cfop, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer);
        $produtoText .= Registro1200::processar($valoresTotaisSegmentadoPorCfop, $isZeraIcms);
        $produtoText .= Registro1500::processar($xml, $doc, $cfop, $cfopEquivalente, $tageed['percentual']);


 

        return $produtoText;
    }
}
