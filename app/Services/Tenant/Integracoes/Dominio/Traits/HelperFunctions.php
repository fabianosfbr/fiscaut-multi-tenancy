<?php

namespace App\Services\Tenant\Integracoes\Dominio\Traits;

use App\Models\Tenant\Acumulador;
use App\Models\Tenant\Cest;
use App\Models\Tenant\CategoryTag;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\ProdutoFornecedor;
use App\Models\Tenant\OrganizacaoConfiguracao;

class HelperFunctions
{

    public static function processaProduto($xml)
    {
        $produtos = [];

        foreach ($xml->NFe->infNFe->det as $det) {
            // Verifica se existem campos do Simples Nacional no item
            $vCredICMSSN = 0;
            $pCredSN = 0;
            $csosn = '';

            // Verificar se existe o grupo ICMSSN101 a ICMSSN900
            $icmsSNTags = [
                'ICMSSN101',
                'ICMSSN102',
                'ICMSSN201',
                'ICMSSN202',
                'ICMSSN500',
                'ICMSSN900'
            ];

            foreach ($icmsSNTags as $tag) {
                if (isset($det->imposto->ICMS->{$tag})) {
                    $icmsSN = $det->imposto->ICMS->{$tag};
                    $csosn = (string) ($icmsSN->CSOSN ?? '');

                    // Nem todos os CSOSNs têm esses campos
                    if (isset($icmsSN->pCredSN)) {
                        $pCredSN = (float) $icmsSN->pCredSN;
                    }

                    if (isset($icmsSN->vCredICMSSN)) {
                        $vCredICMSSN = (float) $icmsSN->vCredICMSSN;
                    }

                    break;
                }
            }
            $item = [
                'numero_item' => (int) $det['nItem'],
                'codigo' => (string) $det->prod->cProd,
                'codigo_barras' => (string) $det->prod->cEAN,
                'descricao' => (string) $det->prod->xProd,
                'ncm' => (string) $det->prod->NCM,
                'cest' => (string) ($det->prod->CEST ?? ''),
                'cfop' => (string) $det->prod->CFOP,
                'unidade' => (string) $det->prod->uCom,
                'quantidade' => (float) $det->prod->qCom,
                'valor_unitario' => (float) $det->prod->vUnCom,
                'valor_total' => (float) $det->prod->vProd,
                'valor_desconto' => (float) ($det->prod->vDesc ?? 0),
                'valor_frete' => (float) ($det->prod->vFrete ?? 0),
                'valor_seguro' => (float) ($det->prod->vSeg ?? 0),
                'valor_outras_despesas' => (float) ($det->prod->vOutro ?? 0),
                'cean_trib' => (string) ($det->prod->cEANTrib ?? ''),
                'cod_importacao' => (string) ($det->prod->codImport ?? ''),
                'valor_outro' => (float) ($det->prod->vOutro ?? 0),
                'valor_total_tributo' => (float) ($det->imposto->vTotTrib ?? 0),

                // Dados do ICMS
                'origem' => (string) ($det->imposto->ICMS->ICMS00->orig ??
                    $det->imposto->ICMS->ICMS10->orig ??
                    $det->imposto->ICMS->ICMS20->orig ??
                    $det->imposto->ICMS->ICMSSN101->orig ??
                    $det->imposto->ICMS->ICMSSN102->orig ??
                    $det->imposto->ICMS->ICMSSN201->orig ??
                    $det->imposto->ICMS->ICMSSN202->orig ??
                    $det->imposto->ICMS->ICMSSN500->orig ??
                    $det->imposto->ICMS->ICMSSN900->orig ?? ''),
                'cst_icms' => (string) ($det->imposto->ICMS->ICMS00->CST ??
                    $det->imposto->ICMS->ICMS10->CST ??
                    $det->imposto->ICMS->ICMS20->CST ?? ''),
                'csosn' => $csosn,
                'valor_icms_st' => (float) ($det->imposto->ICMS->vICMSST ?? 0),
                'aliquota_icms_st' => (float) ($det->imposto->ICMS->pICMSST ?? 0),
                'base_calculo_icms' => (float) ($det->imposto->ICMS->ICMS00->vBC ??
                    $det->imposto->ICMS->ICMS10->vBC ??
                    $det->imposto->ICMS->ICMS20->vBC ?? 0),
                'aliquota_icms' => (float) ($det->imposto->ICMS->ICMS00->pICMS ??
                    $det->imposto->ICMS->ICMS10->pICMS ??
                    $det->imposto->ICMS->ICMS20->pICMS ?? 0),
                'valor_icms' => (float) ($det->imposto->ICMS->ICMS00->vICMS ??
                    $det->imposto->ICMS->ICMS10->vICMS ??
                    $det->imposto->ICMS->ICMS20->vICMS ?? 0),
                'valor_icms_deson' => (float) ($det->imposto->ICMS->ICMS00->vICMSDeson ??
                    $det->imposto->ICMS->ICMS10->vICMSDeson ??
                    $det->imposto->ICMS->ICMS20->vICMSDeson ?? 0),

                // Campos do Simples Nacional
                'aliquota_credito_icmssn' => $pCredSN,
                'valor_credito_icmssn' => $vCredICMSSN,

                'valor_ii' => (float) ($det->imposto->II->vII ?? 0),
                'valor_fcp' => (float) ($det->imposto->ICMS->vFCP ?? 0),
                'aliquota_fcp' => (float) ($det->imposto->ICMS->pFCP ?? 0),
                'valor_fcp_st' => (float) ($det->imposto->ICMS->vFCPST ?? 0),
                'aliquota_fcp_st' => (float) ($det->imposto->ICMS->pFCPST ?? 0),
                'valor_fcp_str' => (float) ($det->imposto->ICMS->vFCPSTRet ?? 0),
                'aliquota_fcp_str' => (float) ($det->imposto->ICMS->pFCPSTRet ?? 0),
                'valor_bc_st' => (float) ($det->imposto->ICMS->vBCST ?? 0),
                'aliquota_bc_st' => (float) ($det->imposto->ICMS->pBCST ?? 0),
                'valor_st' => (float) ($det->imposto->ICMS->vST ?? 0),
                'aliquota_st' => (float) ($det->imposto->ICMS->pST ?? 0),



                // Dados do IPI
                'cst_ipi' => (string) ($det->imposto->IPI->IPITrib->CST ?? ''),
                'base_calculo_ipi' => (float) ($det->imposto->IPI->IPITrib->vBC ?? 0),
                'aliquota_ipi' => (float) ($det->imposto->IPI->IPITrib->pIPI ?? 0),
                'valor_ipi' => (float) ($det->imposto->IPI->IPITrib->vIPI ?? 0),
                'valor_ipi_devolucao' => (float) ($det->imposto->IPI->IPIDevol->vIPIDevol ?? 0),

                // Dados do PIS
                'cst_pis' => (string) ($det->imposto->PIS->PISAliq->CST ?? ''),
                'base_calculo_pis' => (float) ($det->imposto->PIS->PISAliq->vBC ?? 0),
                'aliquota_pis' => (float) ($det->imposto->PIS->PISAliq->pPIS ?? 0),
                'valor_pis' => (float) ($det->imposto->PIS->PISAliq->vPIS ?? 0),

                // Dados do COFINS
                'cst_cofins' => (string) ($det->imposto->COFINS->COFINSAliq->CST ?? ''),
                'base_calculo_cofins' => (float) ($det->imposto->COFINS->COFINSAliq->vBC ?? 0),
                'aliquota_cofins' => (float) ($det->imposto->COFINS->COFINSAliq->pCOFINS ?? 0),
                'valor_cofins' => (float) ($det->imposto->COFINS->COFINSAliq->vCOFINS ?? 0),
            ];

            $produtos[] = $item;
        }

        return $produtos;
    }

    public static function registrarProduto($produtos, $doc)
    {
        foreach ($produtos as $produto) {

            ProdutoFornecedor::firstOrCreate([
                'cnpj' => $doc->cnpj_emitente,
                'num_nfe' => $doc->numero,
                'serie_nfe' => $doc->serie,
                'codigo_produto' => $produto['codigo'],
                'descricao_produto' => $produto['descricao'],
                'unidade_comercializada' => $produto['unidade'],
            ], [
                'cnpj' => $doc->cnpj_emitente,
                'num_nfe' => $doc->numero,
                'serie_nfe' => $doc->serie,
                'codigo_produto' => $produto['codigo'],
                'descricao_produto' => $produto['descricao'],
                'unidade_comercializada' => $produto['unidade'],
                'external_id' => str()->random(14),
            ]);
        }
    }

    public static function uniqueCfops($produtos)
    {

        $cfops = [];
        foreach ($produtos as $produto) {
            if (!in_array($produto['cfop'], $cfops)) {
                $cfops[] = $produto['cfop'];
            }
        }
        return $cfops;
    }

    public static function checkExportacao($cfops)
    {
        $cfopsImportacao = [3101, 3102, 3127, 3551, 3949];

        $resultado = array_intersect($cfopsImportacao, $cfops);

        return count($resultado) > 0;
    }

    public static function checkEntradaPropria($nota, $currentIssuer)
    {
        if ($nota->tipo == '0' && $currentIssuer->cnpj == $nota->cnpj_emitente) {
            return true;
        }
        return false;
    }


    public static function checkEntradaTerceiro($nota, $currentIssuer)
    {
        if ($nota->tipo == '1' && $currentIssuer->cnpj == $nota->cnpj_destinatario) {
            return true;
        }
        return false;
    }

    public static function checkEntradaTerceiroPropria($nota, $currentIssuer)
    {
        if ($nota->tipo == '0' && $currentIssuer->cnpj == $nota->cnpj_destinatario && $currentIssuer->cnpj != $nota->cnpj_emitente) {
            return true;
        }
        return false;
    }

    public static function verificaTipoNfe($nota, $currentIssuer)
    {
        if (self::checkEntradaPropria($nota, $currentIssuer)) {
            return 'nfe_propria';
        }

        if (self::checkEntradaTerceiro($nota, $currentIssuer)) {
            return 'nfe_terceiro';
        }
    }

    public static function verificaTipoCte($nota, $currentIssuer)
    {
        //todo
        return true;
    }

    public static function processaEntradaTerceiro($xml)
    {

        $emit = $xml->NFe->infNFe->emit;

        $registro = '|0020|'; //1;
        $registro .= $emit->CNPJ . '|'; //2
        $registro .= $emit->xNome . '|'; //3
        $registro .= $emit->xNome . '|'; //4
        $registro .= $emit->enderEmit->xLgr . '|'; //5
        $registro .= $emit->enderEmit->nro . '|'; //6
        $registro .= $emit->enderEmit->compl . '|'; //7
        $registro .= $emit->enderEmit->xBairro . '|'; //8
        $registro .= $emit->enderEmit->xMun . '|'; //9 Pode não ter o código do município
        $registro .= $emit->enderEmit->UF . '|'; //10
        $registro .= $emit->enderEmit->cPais . '|'; //11
        $registro .= $emit->enderEmit->CEP . '|'; //12
        $registro .= $emit->IE . '|'; //13
        $registro .= $emit->IM . '|'; //14
        $registro .= $emit->ISUF . '|'; //15
        $registro .= '00|'; //16
        $registro .= $emit->enderEmit->fone . '|'; //17


        return  $registro . "|" . PHP_EOL;
    }

    public static function processaEntradaPropria($xml)
    {
        $dest = $xml->NFe->infNFe->dest;


        $registro = '|0020|'; //1;
        $registro .= $dest->CNPJ . '|'; //2
        $registro .= $dest->xNome . '|'; //3
        $registro .= $dest->xNome . '|'; //4
        $registro .= $dest->enderDest->xLgr . '|'; //5
        $registro .= $dest->enderDest->nro . '|'; //6
        $registro .= $dest->enderDest->compl . '|'; //7
        $registro .= $dest->enderDest->xBairro . '|'; //8
        $registro .= $dest->enderDest->xMun . '|'; //9
        $registro .= $dest->enderDest->UF . '|'; //10
        $registro .= $dest->enderDest->cPais . '|'; //11
        $registro .= $dest->enderDest->CEP . '|'; //12
        $registro .= $dest->IE . '|'; //13
        $registro .= $dest->IM . '|'; //14
        $registro .= $dest->ISUF . '|'; //15
        $registro .= '00|'; //16
        $registro .= $dest->enderDest->fone . '|'; //17

        return  $registro . "|" . PHP_EOL;
    }

    public static function processaEntradaPropriaExportacao($xml)
    {
        $dest = $xml->NFe->infNFe->dest;


        $registro = '|0020|'; //1;
        $registro .= '00000000000000' . '|'; //2
        $registro .= $dest->xNome . '|'; //3
        $registro .= $dest->xNome . '|'; //4
        $registro .= $dest->enderDest->xLgr . '|'; //5
        $registro .= $dest->enderDest->nro . '|'; //6
        $registro .= $dest->enderDest->compl . '|'; //7
        $registro .= $dest->enderDest->xBairro . '|'; //8
        $registro .= '' . '|'; //9
        $registro .= 'EX' . '|'; //10
        $registro .= '' . '|'; //11
        $registro .= $dest->enderDest->CEP . '|'; //12
        $registro .= $dest->IE . '|'; //13
        $registro .= $dest->IM . '|'; //14
        $registro .= $dest->ISUF . '|'; //15
        $registro .= '00|'; //16
        $registro .= $dest->enderDest->fone . '|'; //17

        return  $registro . "|" . PHP_EOL;
    }

    public static function getCategoriaEtiqueta($doc)
    {
        if ($doc->tagged->count() > 0) {

            $tagged = $doc->tagged()->get()->toArray()[0];

            $categorias = Cache::remember('categorias_issuer_' . $tagged['tag']['category']['organization_id'], 1800, function () use ($tagged) {

                return CategoryTag::where('organization_id', $tagged['tag']['category']['organization_id'])->get();
            });

            return $categorias->where('id', $tagged['tag']['category_id'])->first();
        }

        return null;
    }

    public static function removerProdutosRepetidos($produtos)
    {
        //com base na descrição do produto e a unidade comercializada, remover os produtos repetidos
        $produtosFiltrados = [];
        $chaves = [];

        foreach ($produtos as $produto) {
            // Cria uma chave única baseada na descrição e unidade
            $chave = md5($produto['descricao'] . '-' . $produto['unidade']);

            // Se a chave não existir, adiciona o produto à lista filtrada
            if (!in_array($chave, $chaves)) {
                $chaves[] = $chave;
                $produtosFiltrados[] = $produto;
            }
        }

        return $produtosFiltrados;
    }

    public static function getProdutoGenerico($tageeds, $currentIssuer)
    {

        // Extrai os IDs das etiquetas do array de tagged
        $etiquetas = collect($tageeds)->pluck('tag.id')->toArray();

        // Obtém a configuração
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $currentIssuer->id,
            tipo: 'entrada',
            subtipo: 'produtos_genericos'
        );


        // Extrai os IDs das tags da configuração
        $configTags = collect($config['itens'] ?? [])->pluck('tag_id')->toArray();

        // Verifica se há interseção entre as etiquetas
        $tagsEncontradas = array_intersect($etiquetas, $configTags);

        if (empty($tagsEncontradas)) {
            return [];
        }

        // Filtra os itens da configuração que correspondem às tags encontradas
        $produtoGenerico = [];
        foreach ($config['itens'] as $item) {
            if (in_array($item['tag_id'], $tagsEncontradas)) {
                $produtoGenerico = $item['produtos'];
            }
        }

        return $produtoGenerico[0];
    }

    public static function formatarDecimal($valor, $decimal = 3)
    {
        if (is_float($valor)) {
            return number_format($valor, $decimal, ',', '');
        }

        return number_format(floatval($valor), $decimal, ',', '');
    }

    public static function getCest($ncm)
    {
        $valueSearch = substr($ncm, 0, 4);

        $cests = Cache::remember('tabela_cest', now()->addDay(), function () {

            return Cest::all();
        });

        $value = $cests->filter(function ($item) use ($valueSearch) {

            return false !== stripos($item->ncm, $valueSearch);
        });

        return $value->first()?->cest;
    }

    public static function extrairValoresTotaisNota($xml)
    {

        $valores = [

            'vBC' => (float) $xml->NFe->infNFe->total->ICMSTot->vBC,
            'vICMS' => (float) $xml->NFe->infNFe->total->ICMSTot->vICMS,
            'vICMSDeson' => (float) $xml->NFe->infNFe->total->ICMSTot->vICMSDeson,
            'vFCPUFDest' => (float) $xml->NFe->infNFe->total->ICMSTot->vFCPUFDest,
            'vICMSUFDest' => (float) $xml->NFe->infNFe->total->ICMSTot->vICMSUFDest,
            'vICMSUFRemet' => (float) $xml->NFe->infNFe->total->ICMSTot->vICMSUFRemet,
            'vProd' => (float) $xml->NFe->infNFe->total->ICMSTot->vProd,
            'vFrete' => (float) $xml->NFe->infNFe->total->ICMSTot->vFrete,
            'vSeg' => (float) $xml->NFe->infNFe->total->ICMSTot->vSeg,
            'vDesc' => (float) $xml->NFe->infNFe->total->ICMSTot->vDesc,
            'vII' => (float) $xml->NFe->infNFe->total->ICMSTot->vII,
            'vIPI' => (float) $xml->NFe->infNFe->total->ICMSTot->vIPI,
            'vIPIDevol' => (float) $xml->NFe->infNFe->total->ICMSTot->vIPIDevol,
            'vNF' => (float) $xml->NFe->infNFe->total->ICMSTot->vNF,
            'vTotTrib' => (float) $xml->NFe->infNFe->total->ICMSTot->vTotTrib,
            'vFCP' => (float) $xml->NFe->infNFe->total->ICMSTot->vFCP,
            'vFCPST' => (float) $xml->NFe->infNFe->total->ICMSTot->vFCPST,
            'vFCPSTRet' => (float) $xml->NFe->infNFe->total->ICMSTot->vFCPSTRet,
            'vBCST' => (float) $xml->NFe->infNFe->total->ICMSTot->vBCST,
            'vST' => (float) $xml->NFe->infNFe->total->ICMSTot->vST,
            'vPIS' => (float) $xml->NFe->infNFe->total->ICMSTot->vPIS,
            'vCOFINS' => (float) $xml->NFe->infNFe->total->ICMSTot->vCOFINS,
            'vOutro' => (float) $xml->NFe->infNFe->total->ICMSTot->vOutro,

        ];

        return $valores;
    }

    public static function isZeraIcms($tageeds, $currentIssuer)
    {
        // Extrai os IDs das etiquetas do array de tagged
        $etiquetas = collect($tageeds)->pluck('tag.id')->toArray();

        // Obtém a configuração
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $currentIssuer->id,
            tipo: 'entrada',
            subtipo: 'impostos',
            categoria: 'configuracoes',
        );

        // Extrai os IDs das tags da configuração
        $configTags = collect($config['itens'] ?? [])->pluck('tag_id')->toArray();

        // Verifica se há interseção entre as etiquetas
        $tagsEncontradas = array_intersect($etiquetas, $configTags);


        if (empty($tagsEncontradas)) {
            return false;
        }

        foreach ($config['itens'] as $item) {
            if (in_array($item['tag_id'], $tagsEncontradas)) {

                return $item['zerar_icms'] ?? false;
            }
        }

        return false;
    }

    public static function isZeraIpi($tageeds, $currentIssuer)
    {

        // Extrai os IDs das etiquetas do array de tagged
        $etiquetas = collect($tageeds)->pluck('tag.id')->toArray();

        // Obtém a configuração
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $currentIssuer->id,
            tipo: 'entrada',
            subtipo: 'impostos',
            categoria: 'configuracoes',
        );

        // Extrai os IDs das tags da configuração
        $configTags = collect($config['itens'] ?? [])->pluck('tag_id')->toArray();

        // Verifica se há interseção entre as etiquetas
        $tagsEncontradas = array_intersect($etiquetas, $configTags);


        if (empty($tagsEncontradas)) {
            return false;
        }

        foreach ($config['itens'] as $item) {
            if (in_array($item['tag_id'], $tagsEncontradas)) {
                return $item['zerar_ipi'] ?? false;;
            }
        }

        return false;
    }

    public static function calcularPercentualEtiqueta($tageeds, $valoresTotais)
    {
        $valorTotal = $valoresTotais['vNF'];

        foreach ($tageeds as $key => $tageed) {
            $percentual = round($tageed['value'] / $valorTotal, 10);
            $tageeds[$key]['percentual'] = $percentual;
        }
        return $tageeds;
    }



    public static function calcularValorSegmentadoPorCfop($valoresPorProduto)
    {
        $valoresPorCfop = [];

        foreach ($valoresPorProduto as $produto) {
            $cfop = $produto['cfop'];
            
            if (!isset($valoresPorCfop[$cfop])) {
                $valoresPorCfop[$cfop] = [
                    'vProd' => 0,
                    'vFrete' => 0,
                    'vSeg' => 0,
                    'vDesc' => 0,
                    'vII' => 0,
                    'vIPI' => 0,
                    'vBcIPI' => 0,
                    'pIPI' => 0,
                    'vIPIDevol' => 0,
                    'vNF' => 0,
                    'vTotTrib' => 0,
                    'vFCP' => 0,
                    'vICMSST' => 0,
                    'vFCPST' => 0,
                    'vFCPSTRet' => 0,
                    'vBcICMS' => 0,
                    'vICMS' => 0,
                    'pICMS' => 0,
                    'vBCST' => 0,
                    'vST' => 0,
                    'vPIS' => 0,
                    'pPIS' => 0,
                    'vBCPIS' => 0,
                    'vBCOFINS' => 0,
                    'pCOFINS' => 0,
                    'vCOFINS' => 0,
                    'vCSTCOFINS' => 0,
                    'vOutro' => 0,
                    'vContabil' => 0,
                    'csosn' => null,
                    'cst_icms' => null,
                    'cst_ipi' => null,
                    'valor_credito_icmssn' => null,
                    'aliquota_credito_icmssn' => null,
                ];
            }

            $valoresPorCfop[$cfop]['vProd'] += $produto['valor_total'];
            $valoresPorCfop[$cfop]['vFrete'] += $produto['valor_frete'];
            $valoresPorCfop[$cfop]['vSeg'] += $produto['valor_seguro'];
            $valoresPorCfop[$cfop]['vDesc'] += $produto['valor_desconto'];
            $valoresPorCfop[$cfop]['vII'] += $produto['valor_ii'];
            $valoresPorCfop[$cfop]['vIPI'] += $produto['valor_ipi'];
            $valoresPorCfop[$cfop]['vIPIDevol'] += $produto['valor_ipi_devolucao'];
            $valoresPorCfop[$cfop]['vNF'] += $produto['valor_total'];
            $valoresPorCfop[$cfop]['vTotTrib'] += $produto['valor_total_tributo'];
            $valoresPorCfop[$cfop]['vFCP'] += $produto['valor_fcp'];
            $valoresPorCfop[$cfop]['vFCPST'] += $produto['valor_fcp_st'];
            $valoresPorCfop[$cfop]['vFCPSTRet'] += $produto['valor_fcp_str'];
            $valoresPorCfop[$cfop]['vBCST'] += $produto['valor_bc_st'];
            $valoresPorCfop[$cfop]['vBcICMS'] += $produto['base_calculo_icms'];
            $valoresPorCfop[$cfop]['vICMS'] += $produto['valor_icms'];
            $valoresPorCfop[$cfop]['pICMS'] = $produto['aliquota_icms'];
            $valoresPorCfop[$cfop]['vST'] += $produto['valor_st'];
            $valoresPorCfop[$cfop]['vPIS'] += $produto['valor_pis'];
            $valoresPorCfop[$cfop]['pPIS'] = $produto['aliquota_pis'];
            $valoresPorCfop[$cfop]['vBCPIS'] += $produto['base_calculo_pis'];
            $valoresPorCfop[$cfop]['pCOFINS'] = $produto['aliquota_cofins'];
            $valoresPorCfop[$cfop]['vCOFINS'] += $produto['valor_cofins'];
            $valoresPorCfop[$cfop]['vBCOFINS'] += $produto['base_calculo_cofins'];
            $valoresPorCfop[$cfop]['vCSTCOFINS'] += $produto['cst_cofins'];
            $valoresPorCfop[$cfop]['vOutro'] += $produto['valor_outro'];
            $valoresPorCfop[$cfop]['vICMSST'] += $produto['valor_icms_st'];
            $valoresPorCfop[$cfop]['pICMSST'] = $produto['aliquota_icms_st'];
            $valoresPorCfop[$cfop]['vICMS'] += $produto['valor_icms'];
            $valoresPorCfop[$cfop]['vIPI'] += $produto['valor_ipi'];
            $valoresPorCfop[$cfop]['vBcIPI'] += $produto['base_calculo_ipi'];
            $valoresPorCfop[$cfop]['pIPI'] = $produto['aliquota_ipi'];
            $valoresPorCfop[$cfop]['vIPIDevol'] += $produto['valor_ipi_devolucao'];
            $valoresPorCfop[$cfop]['CST'] = $produto['cst_icms'] ?? null;
            $valoresPorCfop[$cfop]['csosn'] = $produto['csosn'] ?? null;
            $valoresPorCfop[$cfop]['CSTIPI'] = $produto['cst_ipi'] ?? null;
            $valoresPorCfop[$cfop]['vCredICMSSN'] = $produto['valor_credito_icmssn'] ?? null;
            $valoresPorCfop[$cfop]['pCredSN'] = $produto['aliquota_credito_icmssn'] ?? null;

            $valoresPorCfop[$cfop]['vContabil'] = $valoresPorCfop[$cfop]['vProd'] + $valoresPorCfop[$cfop]['vFrete'] + $valoresPorCfop[$cfop]['vIPI'] + $valoresPorCfop[$cfop]['vICMSST'];
        }

        return $valoresPorCfop;
    }

    public static function checkIssuerType($type, $currentIssuer)
    {
        return in_array($type, $currentIssuer->atividade);
    }

    public static function getCfopEquivalente($tag, $cfop, $currentIssuer, $doc)
    {

        $categoria = self::verificaTipoNfe($doc, $currentIssuer); //nfe_terceiro

        // Obtém a configuração
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $currentIssuer->id,
            tipo: 'entrada',
            subtipo: 'cfops',
            categoria: $categoria,
        );

        // Se não houver configuração ou itens, retorna o CFOP original
        if (empty($config['itens'])) {
            return $cfop;
        }

        $verificarUf = config_organizacao($currentIssuer->id, 'geral', null, null, 'cfop_verificar_uf', false);

        $listaUf = config('estados_e_municipios.estados');

        foreach ($config['itens'] as $item) {
            // Verifica se a tag corresponde
            if ($item['tag_id'][0] == $tag['id']) {
                // Verifica se o CFOP de saída está na configuração
                if ($verificarUf) {

                    $uf_emitente_nota = $doc->uf_emitente;
                    $uf_issuer = $listaUf[substr($currentIssuer->cod_municipio_ibge, 0, 2)]['sigla'];

                    if ($item['cfops'][0]['aplicar_uf_diferente'] && $uf_issuer != $uf_emitente_nota && in_array($cfop, explode(',', $item['cfops'][0]['cfops_saida']))) {

                        return $item['cfops'][0]['cfop_entrada'];
                    }

                    if (!$item['cfops'][0]['aplicar_uf_diferente'] && $uf_issuer == $uf_emitente_nota && in_array($cfop, explode(',', $item['cfops'][0]['cfops_saida']))) {

                        return $item['cfops'][0]['cfop_entrada'];
                    }
                } else {
                    // Retorna o CFOP de entrada correspondente                   
                    return $item['cfops'][0]['cfop_entrada'];
                }
            }
        }
        // Se não encontrar correspondência, retorna o CFOP original
        return $cfop;
    }

    public static function getAcumulador($tag, $cfop, $currentIssuer, $doc)
    {

        $categoria = self::verificaTipoNfe($doc, $currentIssuer);

        // Obtém a configuração
        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $currentIssuer->id,
            tipo: 'entrada',
            subtipo: 'acumuladores',
            categoria: $categoria,
        );

        // Se não houver configuração ou itens, retorna o CFOP original
        if (empty($config['itens'])) {
            return '';
        }

        foreach ($config['itens'] as $item) {

            if ($item['tag_id'][0] == $tag['id'] && in_array($cfop, explode(',', $item['cfops']))) {

                return $item['codigo_acumulador'];
            }
        }

        return '';
    }

    public static function checkTipoFrete($modFrete)
    {
        $texto = '';
        switch ($modFrete) {
            case 0:
                $texto = 'C';
                break;
            case 1:
                $texto = 'F';
                break;
            case 2:
                $texto = 'T';
                break;
            case 3:
                $texto = 'R';
                break;
            case 4:
                $texto = 'D';
                break;
            case 9:
                $texto = 'S';
                break;
        }

        return $texto;
    }

    public static function ajustaIpi($valor, $tageed, $currentIssuer, $isIndustria)
    {
        if (!isset($tageed['tag'])) {
            return '';
        }

        $conversaoIpi = self::verificaConversaoImposto($tageed['tag'], $currentIssuer, false, true);

        // Retorna '49' se houver conversão de IPI e:
        // - valor for zero E for indústria, OU
        // - valor for maior que zero
        return ($conversaoIpi && ($valor > 0 || ($valor == 0 && $isIndustria))) ? '49' : '00';
    }

    private static function verificaConversaoImposto($tag, $issuer, $icms = false, $ipi = false)
    {

        $config = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $issuer->id,
            tipo: 'entrada',
            subtipo: 'impostos',
            categoria: 'configuracoes',
        );

        // Se não houver configuração ou itens, retorna o CFOP original
        if (empty($config['itens'])) {
            return false;
        }

        $etiqueta = $tag['id'];

        $configTags = collect($config['itens'] ?? [])->pluck('tag_id')->toArray();

        if (in_array($etiqueta, $configTags)) {

            $configItem = collect($config['itens'] ?? [])->firstWhere('tag_id', $etiqueta);

            if ($icms) {
                return $configItem['zerar_icms'] ?? false;
            }

            if ($ipi) {
                return $configItem['zerar_ipi'] ?? false;
            }
        }
        return false;
    }

    public static function validaOpcaoCreditoIcms($valores, $doc, $issuer)
    {
        // Se não há configuração para considerar crédito ICMS, retorna falso rapidamente
        $considera_credito_icms = config_organizacao($issuer->id, 'geral', null, null, 'icms_credito_cfop_1401', false);
        if (!$considera_credito_icms) {
            return false;
        }

        // Obter as etiquetas associadas ao documento
        $etiquetas = collect($doc->tagged()->get())->pluck('tag.id')->toArray();
        $tags_com_credito_icms = config_organizacao($issuer->id, 'geral', null, null, 'tags_com_credito_icms', false);

        // Verificar interseção entre as etiquetas do documento e as tags com crédito ICMS
        $possui_etiqueta_com_credito_icms = !empty(array_intersect($etiquetas, $tags_com_credito_icms));

        // Retorna verdadeiro se tem etiqueta com crédito ICMS e o CST é 10, 30 ou 70
        return $possui_etiqueta_com_credito_icms && in_array($valores['CST'], ['10', '30', '70']);
    }

    public static function converterCSTICMS($cst)
    {
        $result = '000';

        switch ($cst) {
            case '00':
            case '100':
            case '200':
            case '300':
            case '400':
            case '500':
            case '600':
            case '700':
            case '800':
                $result = '000';
                break;

            case '10':
            case '110':
            case '210':
            case '310':
            case '410':
            case '510':
            case '610':
            case '710':
            case '810':
                $result = '010';
                break;

            case '20':
            case '120':
            case '220':
            case '320':
            case '420':
            case '520':
            case '620':
            case '720':
            case '820':
                $result = '020';
                break;

            case '30':
            case '130':
            case '230':
            case '330':
            case '430':
            case '530':
            case '630':
            case '730':
            case '830':
                $result = '030';
                break;

            case '40':
            case '140':
            case '240':
            case '340':
            case '440':
            case '540':
            case '640':
            case '740':
            case '840':
                $result = '040';
                break;

            case '41':
            case '141':
            case '241':
            case '341':
            case '441':
            case '541':
            case '641':
            case '741':
            case '841':
                $result = '041';
                break;

            case '50':
            case '150':
            case '250':
            case '350':
            case '450':
            case '550':
            case '650':
            case '750':
            case '850':
                $result = '050';
                break;

            case '51':
            case '151':
            case '251':
            case '351':
            case '451':
            case '551':
            case '651':
            case '751':
            case '851':
                $result = '051';
                break;

            case '60':
            case '260':
            case '360':
            case '460':
            case '560':
            case '760':
            case '860':
                $result = '060';
                break;

            case '70':
            case '270':
            case '370':
            case '470':
            case '570':
            case '770':
            case '870':
                $result = '070';
                break;

            case '90':
            case '190':
            case '290':
            case '390':
            case '490':
            case '590':
            case '690':
            case '790':
            case '890':
                $result = '090';
                break;
        }
        return $result;
    }

    public static function converterCSTIPI($cst)
    {
        $result = '0,00';

        switch ($cst) {
            case '50':
                $result = '00';
                break;

            case '51':
                $result = '01';
                break;

            case '52':
                $result = '02';
                break;

            case '53':
                $result = '03';
                break;

            case '54':
                $result = '04';
                break;

            case '55':
                $result = '05';
                break;

            case '99':
                $result = '49';
                break;
        }
        return $result;
    }
}
