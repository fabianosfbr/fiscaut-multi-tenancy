<?php

namespace App\Services\Tenant\Xml;

use Exception;
use SimpleXMLElement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Services\Tenant\Xml\Traits\HasXmlReader;
use App\Services\Tenant\Xml\Traits\HasXmlValidator;

class XmlNfeReaderService
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
                
                // Busca a nota fiscal existente
                $nfe = NotaFiscalEletronica::where('chave_acesso', $chaveAcesso)->first();

                if ($nfe) {
                    // Atualiza apenas os campos que podem ser modificados
                    $camposAtualizaveis = [
                        'status_nota',
                        'status_manifestacao',
                        'data_entrada',
                        'origem',
                        'tipo',
                        'xml_content',
                        // Adicione aqui outros campos que podem ser atualizados
                    ];

                    $dadosAtualizacao = array_intersect_key(
                        $this->data,
                        array_flip($camposAtualizaveis)
                    );

                    // Se o status atual for CANCELADA, não permite alteração para AUTORIZADA
                    if ($nfe->status_nota === 'CANCELADA' && $dadosAtualizacao['status_nota'] === 'AUTORIZADA') {
                        throw new Exception("Não é possível alterar o status de uma nota CANCELADA para AUTORIZADA");
                    }

                    // Atualiza a nota fiscal
                    $nfe->update($dadosAtualizacao);

                    // Registra o histórico de alteração
                    $this->registrarHistoricoAlteracao($nfe, $dadosAtualizacao);

                    return $nfe;
                }

                // Se não existir, cria uma nova nota fiscal
                $nfe = NotaFiscalEletronica::create($this->data);

                // Salva os itens
                foreach ($this->data['itens'] as $item) {
                    $nfe->itens()->create($item);
                }

                return $nfe;
            });
        } catch (Exception $e) {
            Log::error('Erro ao salvar NFe: ' . $e->getMessage());
            throw new Exception('Erro ao salvar dados da NFe no banco: ' . $e->getMessage());
        }
    }

    /**
     * Registra o histórico de alteração da nota fiscal
     */
    private function registrarHistoricoAlteracao(NotaFiscalEletronica $nfe, array $dadosAtualizados): void
    {
        $nfe->historicos()->create([
            'data_alteracao' => now(),
            'campos_alterados' => $dadosAtualizados,
            'usuario_id' => auth()->id() ?? null,
        ]);
    }

    /**
     * Retorna os dados extraídos do XML
     */
    public function getData(): array
    {
        return $this->data;
    }
} 