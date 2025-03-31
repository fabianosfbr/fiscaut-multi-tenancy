<?php

namespace App\Services\Tenant\Xml;

use Exception;
use SimpleXMLElement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\DocumentoReferencia;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Services\Tenant\Xml\Traits\HasXmlReader;
use App\Interfaces\ServicoLeituraDocumentoFiscal;
use App\Services\Tenant\Xml\Traits\HasXmlValidator;
use App\Models\Tenant\ConhecimentoTransporteEletronico;

class XmlNfeReaderService implements ServicoLeituraDocumentoFiscal
{
    // use HasXmlValidator, HasXmlReader;

    private ?SimpleXMLElement $xml = null;
    private array $data = [];
    private string $rawXml;

    /**
     * Inicializa o serviço com o conteúdo XML
     */
    public function loadXml(string $xmlContent): self
    {
        try {
            $this->rawXml = $xmlContent;
            $this->xml = new SimpleXMLElement($xmlContent);
            return $this;
        } catch (Exception $e) {
            Log::error('Erro ao carregar XML: ' . $e->getMessage());
            throw new Exception('XML inválido ou mal formatado');
        }
    }

    /**
     * Extrai e mapeia os dados do XML para um array estruturado
     */
    public function parse(): self
    {
        if (!$this->xml) {
            throw new Exception('XML não foi carregado');
        }

        try {
            // Registra os namespaces necessários
            $this->xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');

            $ide = $this->xml->NFe->infNFe->ide;
            $emit = $this->xml->NFe->infNFe->emit;
            $dest = $this->xml->NFe->infNFe->dest;
            $total = $this->xml->NFe->infNFe->total->ICMSTot;
            
            // Extrai as referências
            $referencias = $this->extrairReferencias();
            $possuiReferencia = !empty($referencias);
            
            
            // Extrai o status da nota do protNFe (se existir)
            $status = 'EMITIDA'; // Status padrão
            if (isset($this->xml->protNFe)) {
                $cStat = (string) $this->xml->protNFe->infProt->cStat;
                switch ($cStat) {
                    case '100': // Autorizado o uso da NF-e
                    case '150': // Autorizado o uso da NF-e, autorização fora de prazo
                        $status = 'AUTORIZADA';
                        break;
                    case '101': // Cancelamento de NF-e homologado
                    case '151': // Cancelamento de NF-e homologado fora de prazo
                        $status = 'CANCELADA';
                        break;
                    case '110': // Uso Denegado
                    case '301': // Uso Denegado: Irregularidade fiscal do emitente
                    case '302': // Uso Denegado: Irregularidade fiscal do destinatário
                        $status = 'DENEGADA';
                        break;
                    default:
                        $status = 'EMITIDA';
                }
            }

            // Define o status do manifesto como PENDENTE por padrão
            $statusManifesto = 'PENDENTE';

            // Se houver informações de evento no XML, verifica o status do manifesto
            if (isset($this->xml->procEventoNFe)) {
                $tpEvento = (string) $this->xml->procEventoNFe->evento->infEvento->tpEvento;
                switch ($tpEvento) {
                    case '210200': // Confirmação da Operação
                        $statusManifesto = 'CONFIRMADA';
                        break;
                    case '210220': // Desconhecimento da Operação
                        $statusManifesto = 'DESCONHECIDA';
                        break;
                    case '210240': // Operação não Realizada
                        $statusManifesto = 'OPERACAO_NAO_REALIZADA';
                        break;
                    case '210210': // Ciência da Operação
                        $statusManifesto = 'CIENCIA';
                        break;
                }
            }

            $enderEmit = $this->xml->NFe->infNFe->emit->enderEmit;
            $enderDest = $this->xml->NFe->infNFe->dest->enderDest;

            $this->data = [
                'chave_acesso' => str_replace('NFe', '', $this->xml->NFe->infNFe['Id']),
                'numero' => (string) $ide->nNF,
                'serie' => (string) $ide->serie,
                'tipo' => (string) $ide->tpNF,
                'data_emissao' => Carbon::parse((string) $ide->dhEmi),
                'cnpj_emitente' => (string) $emit->CNPJ,
                'ie_emitente' => (string) ($emit->IE ?? ''),
                'nome_emitente' => (string) $emit->xNome,
                'cnpj_destinatario' => (string) ($dest->CNPJ ?? $dest->CPF),
                'ie_destinatario' => (string) ($dest->IE ?? ''),
                'nome_destinatario' => (string) $dest->xNome,
                'natureza_operacao' => (string) $this->xml->NFe->infNFe->ide->natOp,
                
                // Dados de referência de NFe
                'possui_referencias' => $possuiReferencia,
                'referencias' => $referencias,
                
                // Valores
                'valor_base_icms' => (float) $total->vBC,
                'valor_icms' => (float) $total->vICMS,
                'valor_total' => (float) $total->vNF,
                'valor_icms_desonerado' => (float) ($total->vICMSDeson ?? 0),
                'valor_fundo_combate_uf_dest' => (float) ($total->vFCPUFDest ?? 0),
                'valor_icms_uf_dest' => (float) ($total->vICMSUFDest ?? 0),
                'valor_icms_uf_remet' => (float) ($total->vICMSUFRemet ?? 0),
                'valor_fcp' => (float) ($total->vFCP ?? 0),
                'valor_base_icms_st' => (float) $total->vBCST,
                'valor_icms_st' => (float) $total->vST,
                'valor_fcp_st' => (float) ($total->vFCPST ?? 0),
                'valor_fcp_st_ret' => (float) ($total->vFCPSTRet ?? 0),
                'valor_produtos' => (float) $total->vProd,
                'valor_frete' => (float) $total->vFrete,
                'valor_seguro' => (float) $total->vSeg,
                'valor_desconto' => (float) $total->vDesc,
                'valor_outras_despesas' => (float) $total->vOutro,
                'valor_imposto_importacao' => (float) ($total->vII ?? 0),                                            
                'valor_ipi' => (float) ($total->vIPI ?? 0),
                'valor_ipi_devolucao' => (float) ($total->vIPIDevol ?? 0),
                'valor_pis' => (float) $total->vPIS,                
                'valor_cofins' => (float) $total->vCOFINS,
                'valor_aproximado_tributos' => (float) ($total->vTotTrib ?? 0),
            
                
                'status_nota' => $status,
                'status_manifestacao' => $statusManifesto,
                'origem' => 'IMPORTADO', // Pode ser: IMPORTADO, SEFAZ ou SIEG
                'xml_content' => $this->rawXml,

                // Dados do Emitente
                'logradouro_emitente' => (string) $enderEmit->xLgr,
                'numero_emitente' => (string) $enderEmit->nro,
                'complemento_emitente' => (string) $enderEmit->xCpl,
                'bairro_emitente' => (string) $enderEmit->xBairro,
                'municipio_emitente' => (string) $enderEmit->xMun,
                'uf_emitente' => (string) $enderEmit->UF,
                'cep_emitente' => (string) $enderEmit->CEP,

                // Dados do Destinatário
                'logradouro_destinatario' => (string) $enderDest->xLgr,
                'numero_destinatario' => (string) $enderDest->nro,
                'complemento_destinatario' => (string) $enderDest->xCpl,
                'bairro_destinatario' => (string) $enderDest->xBairro,
                'municipio_destinatario' => (string) $enderDest->xMun,
                'uf_destinatario' => (string) $enderDest->UF,
                'cep_destinatario' => (string) $enderDest->CEP,
                'telefone_destinatario' => (string) $dest->telefone,
                'email_destinatario' => (string) $dest->email,
            ];

            // Extrai os itens
            $this->data['itens'] = [];
            foreach ($this->xml->NFe->infNFe->det as $det) {
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

                    // Dados do ICMS
                    'origem' => (string) ($det->imposto->ICMS->ICMS00->orig ?? 
                                        $det->imposto->ICMS->ICMS10->orig ?? 
                                        $det->imposto->ICMS->ICMS20->orig ?? ''),
                    'cst_icms' => (string) ($det->imposto->ICMS->ICMS00->CST ?? 
                                          $det->imposto->ICMS->ICMS10->CST ?? 
                                          $det->imposto->ICMS->ICMS20->CST ?? ''),
                    'base_calculo_icms' => (float) ($det->imposto->ICMS->ICMS00->vBC ?? 
                                                   $det->imposto->ICMS->ICMS10->vBC ?? 
                                                   $det->imposto->ICMS->ICMS20->vBC ?? 0),
                    'aliquota_icms' => (float) ($det->imposto->ICMS->ICMS00->pICMS ?? 
                                               $det->imposto->ICMS->ICMS10->pICMS ?? 
                                               $det->imposto->ICMS->ICMS20->pICMS ?? 0),
                    'valor_icms' => (float) ($det->imposto->ICMS->ICMS00->vICMS ?? 
                                           $det->imposto->ICMS->ICMS10->vICMS ?? 
                                           $det->imposto->ICMS->ICMS20->vICMS ?? 0),

                    // Dados do IPI
                    'cst_ipi' => (string) ($det->imposto->IPI->IPITrib->CST ?? ''),
                    'base_calculo_ipi' => (float) ($det->imposto->IPI->IPITrib->vBC ?? 0),
                    'aliquota_ipi' => (float) ($det->imposto->IPI->IPITrib->pIPI ?? 0),
                    'valor_ipi' => (float) ($det->imposto->IPI->IPITrib->vIPI ?? 0),

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

                $this->data['itens'][] = $item;
            }

         

            return $this;
        } catch (Exception $e) {
            Log::error('Erro ao fazer parse do XML: ' . $e->getMessage());
            throw new Exception('Erro ao processar dados do XML: ' . $e->getMessage());
        }
    }

    /**
     * Define a origem do XML
     */
    public function setOrigem(string $origem): self
    {
        if (!in_array($origem, ['IMPORTADO', 'SEFAZ', 'SIEG'])) {
            throw new Exception('Origem inválida. Use: IMPORTADO, SEFAZ ou SIEG');
        }
        
        $this->data['origem'] = $origem;
        return $this;
    }

    /**
     * Salva ou atualiza os dados extraídos no banco de dados
     */
    public function save(): NotaFiscalEletronica
    {
        try {
            return DB::transaction(function () {
                $chaveAcesso = $this->data['chave_acesso'];

                info($chaveAcesso);
                
                // Remove campos que serão tratados separadamente
                $referencias = $this->data['referencias'] ?? [];
                unset($this->data['referencias']);
                unset($this->data['possui_referencias']);
                
                // Busca a nota fiscal existente
                $nfe = NotaFiscalEletronica::where('chave_acesso', $chaveAcesso)->first();
                
                // Atualiza ou cria a nota fiscal
                if ($nfe) {
                    // Remove os itens para garantir que serão atualizados corretamente
                    $nfe->itens()->delete();
                    $nfe->update($this->data);
                } else {
                    $nfe = NotaFiscalEletronica::create($this->data);
                }
                
                // Cria os itens da nota
                if (!empty($this->data['itens'])) {
                    foreach ($this->data['itens'] as $itemData) {
                        $nfe->itens()->create($itemData);
                    }
                }
                
                // Processa as referências
                $this->processarReferencias($nfe, $referencias);
                
                // Retorna a nota fiscal
                return $nfe;
            });
        } catch (Exception $e) {
            Log::error('Erro ao salvar dados da NFe: ' . $e->getMessage());
            throw new Exception('Erro ao salvar dados da NFe: ' . $e->getMessage());
        }
    }



    /**
     * Retorna os dados extraídos do XML
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Extrai as referências a outros documentos contidas na NF-e
     * 
     * @return array Array com as referências encontradas
     */
    private function extrairReferencias(): array
    {
        $referencias = [];

        // Verifica se existe o nó de documentos referenciados
        if (!isset($this->xml->NFe->infNFe->ide->NFref)) {
            return $referencias;
        }
        
        // Percorre todos os nós NFref para extrair as referências
        foreach ($this->xml->NFe->infNFe->ide->NFref as $nfRef) {
            // Referência a uma NF-e
            if (isset($nfRef->refNFe)) {
                $referencias[] = [
                    'chave' => (string) $nfRef->refNFe,
                    'tipo' => 'NFE'
                ];
            }
            
            // Referência a uma NF (modelo 1/1A)
            elseif (isset($nfRef->refNF)) {
                $referencias[] = [
                    'chave' => null,
                    'tipo' => 'NF'
                ];
            }
            
            // Referência a uma NF de produtor rural
            elseif (isset($nfRef->refNFP)) {
                $referencias[] = [
                    'chave' => null,
                    'tipo' => 'NFP',
                    'dados_adicionais' => [
                        'cUF' => (string) $nfRef->refNFP->cUF,
                        'AAMM' => (string) $nfRef->refNFP->AAMM,
                        'CNPJ' => isset($nfRef->refNFP->CNPJ) ? (string) $nfRef->refNFP->CNPJ : null,
                        'CPF' => isset($nfRef->refNFP->CPF) ? (string) $nfRef->refNFP->CPF : null,
                        'IE' => (string) $nfRef->refNFP->IE,
                        'mod' => (string) $nfRef->refNFP->mod,
                        'serie' => (string) $nfRef->refNFP->serie,
                        'nNF' => (string) $nfRef->refNFP->nNF
                    ]
                ];
            }
            
            // Referência a um CT-e
            elseif (isset($nfRef->refCTe)) {
                $referencias[] = [
                    'chave' => (string) $nfRef->refCTe,
                    'tipo' => 'CTE'
                ];
            }
            
            // Referência a uma ECF (Cupom Fiscal)
            elseif (isset($nfRef->refECF)) {
                $referencias[] = [
                    'chave' => null,
                    'tipo' => 'ECF',
                    'dados_adicionais' => [
                        'mod' => (string) $nfRef->refECF->mod,
                        'nECF' => (string) $nfRef->refECF->nECF,
                        'nCOO' => (string) $nfRef->refECF->nCOO
                    ]
                ];
            }
        }
        
        return $referencias;
    }

    /**
     * Processa as referências a outros documentos
     * 
     * @param NotaFiscalEletronica $nfe A NF-e que referencia outros documentos
     * @param array $referencias Lista de referências encontradas no XML
     */
    private function processarReferencias(NotaFiscalEletronica $nfe, array $referencias): void
    {
        if (empty($referencias)) {
            return;
        }
        
        // Limpar referências antigas desta NFe para outros documentos
        DocumentoReferencia::where([
            'documento_origem_type' => get_class($nfe),
            'documento_origem_id' => $nfe->id
        ])->delete();
        
        foreach ($referencias as $referencia) {
            if (!empty($referencia['chave'])) {
                $tipoDocumento = $referencia['tipo']; // 'NFE' ou 'CTE'
                $chaveAcesso = $referencia['chave'];
                
                // Verificar se o documento referenciado existe no sistema
                $documentoReferenciado = null;
                
                if ($tipoDocumento === 'NFE') {
                    $documentoReferenciado = NotaFiscalEletronica::where('chave_acesso', $chaveAcesso)->first();
                } elseif ($tipoDocumento === 'CTE') {
                    $documentoReferenciado = ConhecimentoTransporteEletronico::where('chave_acesso', $chaveAcesso)->first();
                }
                
                // Criar a referência usando o método estático existente
                DocumentoReferencia::criarReferencia(
                    $nfe,                   // documento de origem (NF-e)
                    $chaveAcesso,           // chave de acesso do documento referenciado
                    $tipoDocumento,         // tipo de documento (NFE ou CTE)
                    $documentoReferenciado  // modelo do documento referenciado (se existir)
                );
            }
            // Para documentos sem chave de acesso, como NF modelo 1, ECF, etc.
            // podemos implementar uma lógica específica se necessário
        }
    }
} 