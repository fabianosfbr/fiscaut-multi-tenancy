<?php

namespace App\Services\Tenant\Integracoes\Dominio\Nfe;

use Illuminate\Support\Arr;
use App\Models\Tenant\ProdutoFornecedor;
use App\Services\Tenant\Integracoes\Dominio\Traits\HelperFunctions;

class Registro100
{

    public static function processar($xml, $doc, $currentIssuer, $produtos): string
    {

        $tageeds = $doc->tagged()->get()->toArray();

        $tipo_item = count($tageeds) > 0 ? Arr::get($tageeds[0], 'tag.category.tipo_item') : '';

        $grupoCategoria = HelperFunctions::getCategoriaEtiqueta($doc);

        $produtosSemRepetir = HelperFunctions::removerProdutosRepetidos($produtos);

        $produtoGenerico = HelperFunctions::getProdutoGenerico($tageeds, $currentIssuer);

        $produtoText = '';

        foreach ($produtosSemRepetir as $produto) {

            $produtoFornecedor = ProdutoFornecedor::where('cnpj', $doc->cnpj_emitente)
                ->where('num_nfe', $doc->numero)
                ->where('serie_nfe', $doc->serie)
                ->where('codigo_produto', $produto['codigo'])
                ->where('descricao_produto', $produto['descricao'])
                ->where('unidade_comercializada', $produto['unidade'])
                ->first();

            $produtoText .= self::gerarLinhaProduto($produto, $produtoGenerico, $produtoFornecedor, $grupoCategoria, $tipo_item, $doc, $currentIssuer);

            $produtoText .= Registro135::processar($doc, $produto);

            $produtoText .= Registro150::processar($produto);

        }


        return $produtoText;
    }

    public static function gerarLinhaProduto($produto, $produtoGenerico, $produtoFornecedor, $grupoCategoria, $tipo_item, $doc, $issuer)
    {
        $entradaPropria = HelperFunctions::checkEntradaPropria($doc, $issuer);

        $produtoText = '|0100|'; //1
        $produtoText .= $entradaPropria ? $produto['codigo'] . "|" : $produtoFornecedor?->external_id . "|"; //2
        $produtoText .= str_replace('|', '-', $produto['descricao']) . "|"; //3
        $produtoText .=  '' . "|"; //4
        $produtoText .=  $produto['ncm'] . "|"; //5
        $produtoText .= '0' . "|"; //6
        $produtoText .= $produto['cean_trib'] . "|"; //7
        $produtoText .= $produto['cod_importacao'] . "|"; //8
        $produtoText .=  count($produtoGenerico) > 0 ? '' . "|" : $grupoCategoria?->grupo . "|"; //9
        $produtoText .= $produto['unidade'] . "|"; //10   
        $produtoText .=  'S' . "|"; //11
        $produtoText .=  '' . "|"; //12
        $produtoText .=  '' . "|"; //13
        $produtoText .=  '' . "|"; //14
        $produtoText .=  '' . "|"; //15
        $produtoText .=  '' . "|"; //16
        $produtoText .=  '' . "|"; //17
        $produtoText .=  HelperFunctions::formatarDecimal($produto['valor_unitario']) . "|"; //18
        $produtoText .=  '' . "|"; //19
        $produtoText .=  '' . "|"; //20
        $produtoText .=  '' . "|"; //21
        $produtoText .=  HelperFunctions::formatarDecimal($produto['aliquota_icms'])  . "|"; //22
        $produtoText .=  HelperFunctions::formatarDecimal($produto['aliquota_ipi'])  . "|"; //23
        $produtoText .=  'M' . "|"; //24
        $produtoText .=  '' . "|"; //25
        $produtoText .=  'N' . "|"; //26
        $produtoText .=  '' . "|"; //27
        $produtoText .=  '' . "|"; //28
        $produtoText .=  '' . "|"; //29
        $produtoText .=  '' . "|"; //30
        $produtoText .=  '' . "|"; //31
        $produtoText .=  '' . "|"; //32
        $produtoText .=  '' . "|"; //33
        $produtoText .=  '' . "|"; //34
        $produtoText .=  'N' . "|"; //35
        $produtoText .=  '' . "|"; //36
        $produtoText .=  '' . "|"; //37
        $produtoText .=  '' . "|"; //38
        $produtoText .=  'N' . "|"; //39
        $produtoText .=  '' . "|"; //40
        $produtoText .=  '' . "|"; //41
        $produtoText .=  '' . "|"; //42
        $produtoText .=  'N' . "|"; //43
        $produtoText .=  '' . "|"; //44
        $produtoText .=  '' . "|"; //45
        $produtoText .=  '' . "|"; //46
        $produtoText .=  'N' . "|"; //47
        $produtoText .=  'N' . "|"; //48
        $produtoText .=  '' . "|"; //49
        $produtoText .=  '' . "|"; //50
        $produtoText .=  '' . "|"; //51
        $produtoText .=  'N' . "|"; //52
        $produtoText .=  '' . "|"; //53
        $produtoText .=  '' . "|"; //54
        $produtoText .=  '' . "|"; //55
        $produtoText .=  '' . "|"; //56
        $produtoText .=  '' . "|"; //57
        $produtoText .=  '' . "|"; //58
        $produtoText .=  '' . "|"; //59
        $produtoText .=  '' . "|"; //60
        $produtoText .=  'N' . "|"; //61
        $produtoText .=  '' . "|"; //62
        $produtoText .=  '' . "|"; //63
        $produtoText .=  '' . "|"; //64
        $produtoText .=  '' . "|"; //65
        $produtoText .=  '' . "|"; //66
        $produtoText .=  '' . "|"; //67
        $produtoText .=  $tipo_item . "|"; //68
        $produtoText .=  '' . "|"; //69
        $produtoText .=  $grupoCategoria?->conta_contabil . "|"; //70
        $produtoText .=  '' . "|"; //71
        $produtoText .=  '' . "|"; //72
        $produtoText .=  '' . "|"; //73
        $produtoText .=  '' . "|"; //74
        $produtoText .=  '' . "|"; //75
        $produtoText .=  'N' . "|"; //76
        $produtoText .=  '' . "|"; //77
        $produtoText .=  '' . "|"; //77
        $produtoText .=  '' . "|"; //78
        $produtoText .=  'N' . "|"; //79
        $produtoText .=  '' . "|"; //80
        $produtoText .=  '' . "|"; //81
        $produtoText .=  '' . "|"; //82
        $produtoText .=  '' . "|"; //83
        $produtoText .=  '' . "|"; //84
        $produtoText .=  '' . "|"; //85
        $produtoText .=  '' . "|"; //86
        $produtoText .=  '' . "|"; //87
        $produtoText .=  '' . "|"; //88
        $produtoText .=  HelperFunctions::getCest($produto['ncm']) . "|";   //89
        $produtoText .=  '' . "|"; //90
        $produtoText .=  $entradaPropria ? $produto['codigo'] . "|" : $produtoFornecedor?->external_id . "|"; //91

        return $produtoText .  PHP_EOL;
    }
}
