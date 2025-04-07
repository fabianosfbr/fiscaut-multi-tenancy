<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use App\Models\Tenant\ProdutoFornecedor;
use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro1030
{
    public static function processar($doc, $valoresPorProduto, $percentualTagged, $cfop, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer)
    {

        return self::gerarTextoProdutos($valoresPorProduto, $doc, $percentualTagged, $cfop, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer);
    }


    private static function gerarTextoProdutos($valoresPorProduto, $doc, $percentualTagged, $cfop, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer)
    {
        $produtoText = '';

        foreach ($valoresPorProduto as  $produto) {

            if ($produto['cfop'] == $cfop) {

                $produtoText .= self::gerarLinhaProduto($produto, $percentualTagged, $doc, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer);
            }
        }


        return $produtoText;
    }

    private static function gerarLinhaProduto($produto, $percentual, $doc, $cfopEquivalente, $isZeraIcms, $isZeraIpi, $currentIssuer)
    {        
        $produtoText = '';

        $isEntradaPropria = HelperFunctions::checkEntradaPropria($doc, $currentIssuer);

        $produtoFornecedor = ProdutoFornecedor::where('cnpj', $doc->cnpj_emitente)
            ->where('num_nfe', $doc->numero)
            ->where('serie_nfe', $doc->serie)
            ->where('codigo_produto', $produto['codigo'])
            ->where('descricao_produto', $produto['descricao'])
            ->where('unidade_comercializada', $produto['unidade'])
            ->first();

        $campo13 = 0;
        $campo15 = 0;
        $campo22 = 0;

        if ($isZeraIcms) {
            $campo13 = 0;
        } else {
            if (strlen($produto['csosn']) > 0) {
                $campo13 = $produto['valor_total'] * $percentual;
                $campo15 = $produto['aliquota_credito_icmssn'];
                $campo22 = $produto['valor_credito_icmssn'] * $percentual;
            } else {
                $campo13 = $produto['base_calculo_icms'] * $percentual;
                $campo15 = $produto['aliquota_icms'] * $percentual;
                $campo22 = $produto['valor_icms'] * $percentual;
            }
        }

        $produtoText .= '|1030|'; //1
        $produtoText .= $isEntradaPropria ? $produto['codigo'] . "|" : $produtoFornecedor?->external_id . "|"; //2
        $produtoText .= HelperFunctions::formatarDecimal($produto['quantidade'] * $percentual, 2) . '|'; //3
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_total'] * $percentual, 2) . '|'; //4
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_ipi'] * $percentual, 2) . '|'; //5
        $produtoText .= HelperFunctions::formatarDecimal($produto['base_calculo_ipi'] * $percentual, 2) . '|'; //6
        $produtoText .= '1' . '|'; //7
        $produtoText .= $doc->data_emissao->format('d/m/Y') . "|"; //8
        $produtoText .= '0,00' . "|"; //9
        $produtoText .= $isZeraIcms ? '090' . '|' : HelperFunctions::converterCSTICMS($produto['cst_icms']) . "|"; //10
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_total'] * $percentual, 2) . '|'; //11
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_desconto'], 3) . '|'; //12
        $produtoText .= $isZeraIcms ? '0,00' . '|' : HelperFunctions::formatarDecimal($campo13, 2) . '|'; //13
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_icms_st'] * $percentual, 2) . '|'; //14
        $produtoText .= $isZeraIcms ? '0,00' . '|' : HelperFunctions::formatarDecimal($campo15, 2) . '|'; //15
        $produtoText .= 'N' . '|'; //16
        $produtoText .= '0,00' . '|'; //17
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_frete'] * $percentual, 2) . '|'; //18
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_seguro'] * $percentual, 2) . '|'; //19
        $produtoText .= '0,00' . '|'; //20
        $produtoText .= '0,00' . '|'; //21
        $produtoText .= $isZeraIcms ? '0,00' . '|' : HelperFunctions::formatarDecimal($campo22, 2) . '|'; //22
        $produtoText .= HelperFunctions::formatarDecimal($produto['valor_icms_st'] * $percentual, 2) . '|'; //23
        $produtoText .= '0,00' . '|'; //24
        $produtoText .= '0,00' . '|'; //25
        $produtoText .= '0,00' . '|'; //26
        $produtoText .=  HelperFunctions::formatarDecimal($produto['valor_unitario'], 2) . '|'; //27
        $produtoText .= '0,00' . '|'; //28
        $produtoText .=  $isZeraIpi ? '49' . '|' : HelperFunctions::converterCSTIPI($produto['cst_icms']) . '|'; //29
        $produtoText .= HelperFunctions::formatarDecimal($produto['aliquota_ipi'] * $percentual, 2) . '|'; //30
        $produtoText .= '0,00' . '|'; //31
        $produtoText .= '0,00' . '|'; //32
        $produtoText .= '0,00' . '|'; //33
        $produtoText .=  $cfopEquivalente . '|'; //34
        $produtoText .= '' . '|'; //35
        $produtoText .= '0,00' . '|'; //36
        $produtoText .= '0,00' . '|'; //37
        $produtoText .= '0,00' . '|'; //38
        $produtoText .= '0,00' . '|'; //39
        $produtoText .= '0,00' . '|'; //40
        $produtoText .= '50' . '|'; //41
        $produtoText .= '0,00' . '|'; //42
        $produtoText .= '50' . '|'; //43
        $produtoText .= '0,00' . '|'; //44
        $produtoText .= '' . '|'; //45
        $produtoText .= '9' . '|'; //46
        $produtoText .= '' . '|'; //47
        $produtoText .= '0,00' . '|'; //48
        $produtoText .= $doc->data_emissao->format('d/m/Y') . "|"; //49
        $produtoText .= $doc->data_emissao->format('d/m/Y') . "|"; //50
        $produtoText .= '00' . '|'; //51
        $produtoText .= '0,00' . '|'; //52
        $produtoText .= '' . '|'; //53
        $produtoText .= '' .  '|'; //54
        $produtoText .= '999' .  '|'; //55
        $produtoText .= '' .  '|'; //56
        $produtoText .=  $produto['unidade'] .  '|'; //57
        $produtoText .=  $cfopEquivalente . '|'; //58
        $produtoText .= '0' .  '|'; //59
        $produtoText .=  HelperFunctions::formatarDecimal($produto['valor_total'], 2) .  '|'; //60
        $produtoText .= '0,00' .  '|'; //61
        $produtoText .= '0,00' .  '|'; //62
        $produtoText .= '0,00' .  '|'; //63
        $produtoText .= '0,00' .  '|'; //64
        $produtoText .= '0,00' .  '|'; //65
        $produtoText .= '0,00' .  '|'; //66
        $produtoText .= '01' .  '|'; //67
        $produtoText .= '0' .  '|'; //68
        $produtoText .= '' .  '|'; //69
        $produtoText .= '' .  '|'; //70
        $produtoText .= '' .  '|'; //71
        $produtoText .= '' .  '|'; //72
        $produtoText .= '' .  '|'; //73
        $produtoText .= '0,00' .  '|'; //74
        $produtoText .= '0,00' .  '|'; //75
        $produtoText .= '0,00' .  '|'; //76
        $produtoText .= '0,00' .  '|'; //77
        $produtoText .= '0,00' .  '|'; //78
        $produtoText .= '0' .  '|'; //79
        $produtoText .= '0,00' .  '|'; //80
        $produtoText .= '0' .  '|'; //81
        $produtoText .= '0' .  '|'; //82
        $produtoText .= '0' .  '|'; //83
        $produtoText .= '0' .  '|'; //84
        $produtoText .= '0,00' .  '|'; //85
        $produtoText .= '0,00' .  '|'; //86
        $produtoText .= '0,00' .  '|'; //87
        $produtoText .= '0,00' .  '|'; //88
        $produtoText .= '0,00' .  '|'; //89
        $produtoText .= '' .  '|'; //90
        $produtoText .= '0' .  '|'; //91
        $produtoText .= '0,00' .  '|'; //92
        $produtoText .= '0,00' .  '|'; //93
        $produtoText .= '' .  '|'; //94
        $produtoText .= $produtoFornecedor?->external_id .  '|'; //95 mesmo identificador do 91 100
        $produtoText .= '0,00' .  '|'; //96
        $produtoText .= '0,00' .  '|'; //97
        $produtoText .= '0,00' .  '|'; //98
        $produtoText .= '0,00' .  '|'; //99
        $produtoText .= '0,00' .  '|'; //100
        $produtoText .= '0,00' .  '|'; //101
        $produtoText .= '0,00' .  '|'; //102


        return $produtoText . PHP_EOL;

    }
}
