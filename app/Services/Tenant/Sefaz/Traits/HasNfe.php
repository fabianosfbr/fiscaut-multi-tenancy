<?php

namespace App\Services\Tenant\Sefaz\Traits;

use App\Models\NfeProdut;
use App\Models\Tenant\LogSefazResumoNfe;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Models\Tenant\Produto;
use Illuminate\Support\Facades\Log;

trait HasNfe
{
    public function preparaDadosNfe($element): array
    {

        return [
            'nNF' => $element->value('nfeProc.NFe.infNFe.ide.nNF')->get()[0],
            'nat_op' => $element->value('NFe.infNFe.ide.natOp')->sole(),
            'status_nota' => $element->value('protNFe.infProt.cStat')->sole(),
            'vNfe' => $element->value('NFe.infNFe.total.ICMSTot.vNF')->sole(),
            'data_emissao' => explode('T', $element->value('NFe.infNFe.ide.dhEmi')->sole())[0] . ' ' . explode('-', explode('T', $element->value('NFe.infNFe.ide.dhEmi')->sole())[1])[0],
            'chave' => $element->value('protNFe.infProt.chNFe')->sole(),
            'emitente_razao_social' => $element->value('NFe.infNFe.emit.xNome')->sole(),
            'emitente_cnpj' => $this->verificaTipoDePessoaEmitente($element),
            'emitente_ie' => $element->value('NFe.infNFe.emit.IE')->get() ? $element->value('NFe.infNFe.emit.IE')->sole() : null,
            'enderEmit_xLgr' => $element->value('NFe.infNFe.emit.enderEmit.xLgr')->sole(),
            'enderEmit_nro' => $element->value('NFe.infNFe.emit.enderEmit.nro')->sole(),
            'enderEmit_xBairro' => $element->value('NFe.infNFe.emit.enderEmit.xBairro')->sole(),
            'enderEmit_xMun' => $element->value('NFe.infNFe.emit.enderEmit.xMun')->sole(),
            'enderEmit_UF' => $element->value('NFe.infNFe.emit.enderEmit.UF')->sole(),
            'enderEmit_CEP' => $element->value('NFe.infNFe.emit.enderEmit.CEP')->get() ? $element->value('NFe.infNFe.emit.enderEmit.CEP')->sole() : null,
            'enderEmit_xPais' => $element->value('NFe.infNFe.emit.enderEmit.xPais')->get() ? $element->value('NFe.infNFe.emit.enderEmit.xPais')->sole() : null,
            'enderEmit_fone' => $element->value('NFe.infNFe.emit.enderEmit.fone')->get() ? $element->value('NFe.infNFe.emit.enderEmit.fone')->sole() : null,
            'tpNf' => $element->value('NFe.infNFe.ide.tpNF')->sole(),
            'destinatario_ie' => $element->value('NFe.infNFe.dest.IE')->get() ? $element->value('NFe.infNFe.dest.IE')->sole() : null,
            'destinatario_cnpj' => $this->verificaTipoDePessoaDestinatario($element),
            'destinatario_razao_social' => $element->value('NFe.infNFe.dest.xNome')->sole(),
            'enderDest_xLgr' => $element->value('NFe.infNFe.dest.enderDest.xLgr')->get() ? $element->value('NFe.infNFe.dest.enderDest.xLgr')->sole() : null,
            'enderDest_nro' => $element->value('NFe.infNFe.dest.enderDest.nro')->get() ? $element->value('NFe.infNFe.dest.enderDest.nro')->sole() : null,
            'enderDest_xCpl' => $element->value('NFe.infNFe.dest.enderDest.xCpl')->get() ? $element->value('NFe.infNFe.dest.enderDest.xCpl')->sole() : null,
            'enderDest_xBairro' => $element->value('NFe.infNFe.dest.enderDest.xBairro')->sole(),
            'enderDest_xMun' => $element->value('NFe.infNFe.dest.enderDest.xMun')->get() ? $element->value('NFe.infNFe.dest.enderDest.xMun')->sole() : null,
            'enderDest_UF' => $element->value('NFe.infNFe.dest.enderDest.UF')->get() ? $element->value('NFe.infNFe.dest.enderDest.UF')->sole() : null,
            'enderDest_CEP' => $element->value('NFe.infNFe.dest.enderDest.CEP')->get() ? $element->value('NFe.infNFe.dest.enderDest.CEP')->sole() : null,
            'enderDest_xPais' => $element->value('NFe.infNFe.dest.enderDest.xPais')->get() ? $element->value('NFe.infNFe.dest.enderDest.xPais')->sole() : null,
            'enderDest_fone' => $element->value('NFe.infNFe.dest.enderDest.fone')->get() ? $element->value('NFe.infNFe.dest.enderDest.fone')->sole() : null,
            'transportador_cnpj' => $element->value('NFe.infNFe.transp.transporta.CNPJ')->get() ? $element->value('NFe.infNFe.transp.transporta.CNPJ')->sole() : null,
            'transportador_razao_social' => $element->value('NFe.infNFe.transp.transporta.xNome')->get() ? $element->value('NFe.infNFe.transp.transporta.xNome')->sole() : null,
            'transportador_IE' => $element->value('NFe.infNFe.transp.transporta.IE')->get() ? $element->value('NFe.infNFe.transp.transporta.IE')->sole() : null,
            'transportador_modFrete' => $this->checkTipoFrete($element->value('NFe.infNFe.transp.modFrete')->sole()),
            'transportador_xEnder' => $element->value('NFe.infNFe.transp.transporta.xEnder')->get() ? $element->value('NFe.infNFe.transp.transporta.xEnder')->sole() : null,
            'transportador_xMun' => $element->value('NFe.infNFe.transp.transporta.xMun')->get() ? $element->value('NFe.infNFe.transp.transporta.xMun')->sole() : null,
            'transportador_UF' => $element->value('NFe.infNFe.transp.transporta.UF')->get() ? $element->value('NFe.infNFe.transp.transporta.UF')->sole() : null,
            'aut_xml' => count($element->value('autXML')->get()) > 0 ? $element->value('autXML')->get() : null,
            'nProt' => $element->value('protNFe.infProt.nProt')->sole(),
            'infAdFisco' => $element->value('NFe.infNFe.infAdic.infAdFisco')->get() ? $element->value('NFe.infNFe.infAdic.infAdFisco')->sole() : null,
            'infCpl' => $element->value('NFe.infNFe.infAdic.infCpl')->get() ? $element->value('NFe.infNFe.infAdic.infCpl')->sole() : null,
            'digVal' => $element->value('protNFe.infProt.digVal')->sole(),
            'cobranca' => $element->value('NFe.infNFe.pag')->get(),
            'pagamento' => $element->value('NFe.infNFe.cobr')->get(),
            'vBC' => $element->value('NFe.infNFe.total.ICMSTot.vBC')->sole(),
            'vICMS' => $element->value('NFe.infNFe.total.ICMSTot.vICMS')->sole(),
            'vICMSDeson' => $element->value('NFe.infNFe.total.ICMSTot.vICMSDeson')->sole(),
            'vFCPUFDest' => count($element->value('NFe.infNFe.total.ICMSTot.vFCPUFDest')->get()) > 0 ? $element->value('NFe.infNFe.total.ICMSTot.vFCPUFDest')->sole() : 0,
            'vICMSUFDest' => count($element->value('NFe.infNFe.total.ICMSTot.vICMSUFDest')->get()) > 0 ? $element->value('NFe.infNFe.total.ICMSTot.vICMSUFDest')->sole() : 0,
            'vICMSUFRemet' => count($element->value('NFe.infNFe.total.ICMSTot.vICMSUFRemet')->get()) > 0 ? $element->value('NFe.infNFe.total.ICMSTot.vICMSUFRemet')->sole() : 0,
            'vFCP' => $element->value('NFe.infNFe.total.ICMSTot.vFCP')->sole(),
            'vBCST' => $element->value('NFe.infNFe.total.ICMSTot.vBCST')->sole(),
            'vST' => $element->value('NFe.infNFe.total.ICMSTot.vST')->sole(),
            'vFCPST' => $element->value('NFe.infNFe.total.ICMSTot.vFCPST')->sole(),
            'vFCPSTRet' => $element->value('NFe.infNFe.total.ICMSTot.vFCPSTRet')->sole(),
            'vProd' => $element->value('NFe.infNFe.total.ICMSTot.vProd')->sole(),
            'vFrete' => $element->value('NFe.infNFe.total.ICMSTot.vFrete')->sole(),
            'vSeg' => $element->value('NFe.infNFe.total.ICMSTot.vSeg')->sole(),
            'vDesc' => $element->value('NFe.infNFe.total.ICMSTot.vDesc')->sole(),
            'vII' => $element->value('NFe.infNFe.total.ICMSTot.vII')->sole(),
            'vIPI' => $element->value('NFe.infNFe.total.ICMSTot.vIPI')->sole(),
            'vIPIDevol' => $element->value('NFe.infNFe.total.ICMSTot.vIPIDevol')->sole(),
            'vPIS' => $element->value('NFe.infNFe.total.ICMSTot.vPIS')->sole(),
            'vCOFINS' => $element->value('NFe.infNFe.total.ICMSTot.vCOFINS')->sole(),
            'vOutro' => $element->value('NFe.infNFe.total.ICMSTot.vOutro')->sole(),
            'num_produtos' => count($element->value('NFe.infNFe.det')->get()),
            'cfops' => $this->preparaCfops($element),

        ];
    }

    public function preparaDadosProdutos($produto)
    {
        return [
            'codigo_produto' => searchValueInArray($produto, 'cProd'),
            'descricao_produto' => searchValueInArray($produto, 'xProd'),
            'ncm' => searchValueInArray($produto, 'NCM'),
            'cfop' => searchValueInArray($produto, 'CFOP'),
            'unidade' => searchValueInArray($produto, 'uCom'),
            'quantidade' => searchValueInArray($produto, 'qCom'),
            'valor_unit' => searchValueInArray($produto, 'vUnCom'),
            'valor_total' => searchValueInArray($produto, 'vProd'),
            'valor_desc' => searchValueInArray($produto, 'vDesc') ?? 0,
            'valor_seguro' => searchValueInArray($produto, 'vSeg') ?? 0,
            'valor_frete' => searchValueInArray($produto, 'vFrete') ?? 0,
            'base_icms' => isset($produto['imposto']['ICMS']) ? searchValueInArray($produto['imposto']['ICMS'], 'vBC') : 0,
            'valor_icms' => isset($produto['imposto']['ICMS']) ? searchValueInArray($produto['imposto']['ICMS'], 'vICMS') : 0,
            'aliq_icms' => isset($produto['imposto']['ICMS']) ? searchValueInArray($produto['imposto']['ICMS'], 'pICMS') : 0,
            'cst_icms' => isset($produto['imposto']['ICMS']) ? searchValueInArray($produto['imposto']['ICMS'], 'CST') : null,
            'base_ipi' => isset($produto['imposto']['IPI']) ? searchValueInArray($produto['imposto']['IPI'], 'vBC') : 0,
            'valor_ipi' => isset($produto['imposto']['IPI']) ? searchValueInArray($produto['imposto']['IPI'], 'vIPI') : 0,
            'aliq_ipi' => isset($produto['imposto']['IPI']) ? searchValueInArray($produto['imposto']['IPI'], 'pIPI') : 0,
            'cst_ipi' => (isset($produto['imposto']['IPI'])) ? searchValueInArray($produto['imposto']['IPI'], 'CST') : null,
            'base_pis' => isset($produto['imposto']['PIS']) ? searchValueInArray($produto['imposto']['PIS'], 'vBC') : 0,
            'valor_pis' => isset($produto['imposto']['PIS']) ? searchValueInArray($produto['imposto']['PIS'], 'vPIS') : 0,
            'aliq_pis' => isset($produto['imposto']['PIS']) ? searchValueInArray($produto['imposto']['PIS'], 'pPIS') : 0,
            'cst_pis' => isset($produto['imposto']['PIS']) ? searchValueInArray($produto['imposto']['PIS'], 'CST') : null,
            'base_cofins' => isset($produto['imposto']['COFINS']) ? searchValueInArray($produto['imposto']['COFINS'], 'vBC') : 0,
            'valor_cofins' => isset($produto['imposto']['COFINS']) ? searchValueInArray($produto['imposto']['COFINS'], 'vCOFINS') : 0,
            'aliq_cofins' => isset($produto['imposto']['COFINS']) ? searchValueInArray($produto['imposto']['COFINS'], 'pCOFINS') : 0,
            'cst_cofins' => isset($produto['imposto']['COFINS']) ? searchValueInArray($produto['imposto']['COFINS'], 'CST') : null,
        ];
    }

    private function preparaCfops($element)
    {
        $produtos = $element->value('NFe.infNFe.det')->get();
        array_walk($produtos, function (&$value, $key) use (&$cfops) {
            $cfops[] = $value['prod']['CFOP'];
        });

        $values = array_unique($cfops);
        rsort($values);
        return  $values;
    }

    public function prepareDocs($response, $reader, $origem)
    {
        $maxNSU = $reader->value('maxNSU')->sole();
        $docs = $this->extractDocs($response);

        foreach ($docs as $doc) {

            $numnsu = intval($doc->getAttribute('NSU'));

            $xml = gzdecode(base64_decode($doc->nodeValue));

            $xmlReader = loadXmlReader($xml);

            $this->registerLogNfeContent($this->organization, $numnsu, $maxNSU, $xml);

            $this->exec($xmlReader, $xml, $origem);
        }
    }

    public function exec($xmlReader, $xml, $origem)
    {

        if ($this->checkIsType($xmlReader, 'resEvento')) {

            $this->registerLogNfeEvent($this->organization, $xml, $xmlReader);

            return;
        }

        if ($this->checkIsType($xmlReader, 'procEventoNFe')) {

            $this->registerLogProcNfeEvent($this->organization, $xml, $xmlReader);

            return;
        }

        if ($this->checkIsType($xmlReader, 'resNFe')) {

            $resumo = $xmlReader->value('resNFe')->get()[0];

            LogSefazResumoNfe::updateOrCreate(
                [
                    'chave' => $resumo['chNFe'],
                    'organization_id' => $this->organization->id,
                ],
                [
                    'chave' => $resumo['chNFe'],
                    'cnpj' => $resumo['CNPJ'],
                    'razao_social' => $resumo['xNome'],
                    'iscricao_estadual' => isset($resumo['IE']) ? $resumo['IE'] : null,
                    'tipo_nfe' => $resumo['tpNF'],
                    'valor_nfe' => $resumo['vNF'],
                    'created_at' => date('Y-m-d h:i:s'),
                    'dh_emissao' => explode('T', $resumo['dhRecbto'])[0] . ' ' . explode('-', explode('T', $resumo['dhRecbto'])[1])[0],
                    'organization_id' => $this->organization->id,
                    'xml' => $xml,
                ]
            );

            return;
        }

        if ($this->checkIsType($xmlReader, 'nfeProc')) {

            Log::info('Registrando/Atualizando NFe no Fiscaut - Chave:  ' . $xmlReader->value('protNFe.infProt.chNFe')->sole());
            $params = $this->preparaDadosNfe($xmlReader);

            $params['xml'] = gzcompress($xml);
            $params['origem'] = $origem;

            $nfe = NotaFiscalEletronica::updateOrCreate(
                [
                    'chave' => $params['chave'],
                ],
                $params
            );

            foreach ($xmlReader->value('NFe.infNFe.det')->get() as $product) {

                $paramsProduct = $this->preparaDadosProdutos($product);

                $paramsProduct['cst_ipi'] = is_null($paramsProduct['valor_ipi']) ? null : $paramsProduct['cst_ipi'];
                $paramsProduct['cst_icms'] = is_null($paramsProduct['valor_icms']) ? null : $paramsProduct['cst_icms'];
                $paramsProduct['num_nfe'] = $nfe->nNF;

                $checkProduct = Produto::where('nfe_id', $nfe->id)->where('codigo_produto', $paramsProduct['codigo_produto'])->first();

                if ($checkProduct) {
                    $checkProduct->update($paramsProduct);
                } else {
                    $nfe->products()->create($paramsProduct);
                }
            }

            return;
        }
    }

    public function checkIsType($element, $type)
    {

        if (isset($element->values()[$type])) {

            return true;
        }

        return false;
    }


    public function checkTipoFrete($modFrete)
    {
        $texto = '';
        switch ($modFrete) {
            case 0:
                $texto = '0-Por conta do Emit';
                break;
            case 1:
                $texto = '1-Por conta do Dest';
                break;
            case 2:
                $texto = '2-Por conta de Terceiros';
                break;
            case 3:
                $texto = '3-Próprio por conta do Rem';
                break;
            case 4:
                $texto = '4-Próprio por conta do Dest';
                break;
            case 9:
                $texto = '9-Sem Transporte';
                break;
        }

        return $texto;
    }

    public function checkEmptyOrError($element)
    {
        if (count($element->value('cStat')->get()) > 0) {
            $cStat = $element->value('cStat')->sole();
            if (in_array($cStat, ['137', '656'])) {
                //137 - Nenhum documento localizado, a SEFAZ está te informando para consultar novamente após uma hora a contar desse momento
                //656 - Consumo Indevido, a SEFAZ bloqueou o seu acesso por uma hora pois as regras de consultas não foram observadas
                //nesses dois casos pare as consultas imediatamente e retome apenas daqui a uma hora, pelo menos !!
                Log::info('Log de consulta NFe - SEFAZ - retorno -  ' . $cStat . ' Empresa: ' . explode(':', $this->issuer->razao_social)[0]);

                return true;
            }
        }

        return false;
    }

    public function verificaTipoDePessoaDestinatario($element)
    {

        if (count($element->value('NFe.infNFe.dest.CNPJ')->get()) > 0) {
            return $element->value('NFe.infNFe.dest.CNPJ')->sole();
        } elseif (count($element->value('NFe.infNFe.dest.CPF')->get()) > 0) {
            return $element->value('NFe.infNFe.dest.CPF')->sole();
        } else {
            return null;
        }
    }

    public function verificaTipoDePessoaEmitente($element)
    {

        if (count($element->value('NFe.infNFe.emit.CNPJ')->get()) > 0) {
            return $element->value('NFe.infNFe.emit.CNPJ')->sole();
        } elseif (count($element->value('NFe.infNFe.emit.CPF')->get()) > 0) {
            return $element->value('NFe.infNFe.emit.CPF')->sole();
        } else {
            return null;
        }
    }
}
