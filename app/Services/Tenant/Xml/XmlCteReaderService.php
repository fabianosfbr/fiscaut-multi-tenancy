<?php

namespace App\Services\Tenant\Xml;

use Exception;
use Carbon\Carbon;
use SimpleXMLElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\ConhecimentoTransporte;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use App\Models\Tenant\DocumentoReferencia;
use App\Interfaces\ServicoLeituraDocumentoFiscal;

class XmlCteReaderService implements ServicoLeituraDocumentoFiscal
{
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
            Log::error('Erro ao carregar XML de CTe: ' . $e->getMessage());
            throw new Exception('XML de CTe inválido ou mal formatado');
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
            $this->xml->registerXPathNamespace('cte', 'http://www.portalfiscal.inf.br/cte');

            // Extrai os dados principais
            $ide = $this->xml->CTe->infCte->ide;
            $emit = $this->xml->CTe->infCte->emit;
            $rem = $this->xml->CTe->infCte->rem;
            $dest = $this->xml->CTe->infCte->dest;
            $exped = $this->xml->CTe->infCte->exped;
            $receb = $this->xml->CTe->infCte->receb;
            $vPrest = $this->xml->CTe->infCte->vPrest;
                        
            // Verifica se há documentos referenciados (NFes, outros CTes, etc)
            $possuiReferencia = false;
            $chaveReferenciada = null;
            $tipoReferencia = null;
            
            // Verifica referências a NFes
            if (isset($this->xml->CTe->infCte->infCTeNorm->infDoc->infNFe)) {
                $possuiReferencia = true;
                $refNFe = $this->xml->CTe->infCte->infCTeNorm->infDoc->infNFe;
                $chaveReferenciada = (string) $refNFe->chave;
                $tipoReferencia = 'NFE';
            }
            
            // Verifica referências a outros CTes
            if (isset($this->xml->CTe->infCte->infCTeNorm->infDoc->infCTe)) {
                $possuiReferencia = true;
                $refCTe = $this->xml->CTe->infCte->infCTeNorm->infDoc->infCTe;
                $chaveReferenciada = (string) $refCTe->chave;
                $tipoReferencia = 'CTE';
            }
            
            // Extrai o status da nota do protCTe (se existir)
            $status = 'EMITIDO'; // Status padrão
            if (isset($this->xml->protCTe)) {
                $cStat = (string) $this->xml->protCTe->infProt->cStat;
                switch ($cStat) {
                    case '100': // Autorizado o uso do CT-e
                        $status = 'AUTORIZADO';
                        break;
                    case '101': // Cancelamento homologado
                        $status = 'CANCELADO';
                        break;
                    case '110': // Uso Denegado
                    case '301': // Uso Denegado: Irregularidade fiscal do emitente
                    case '302': // Uso Denegado: Irregularidade fiscal do destinatário
                        $status = 'DENEGADO';
                        break;
                    default:
                        $status = 'EMITIDO';
                }
            }

            $enderEmit = $this->xml->CTe->infCte->emit->enderEmit;
            $enderDest = $this->xml->CTe->infCte->dest->enderDest;
            $enderRem = $this->xml->CTe->infCte->rem->enderReme;
            $enderExped = $this->xml->CTe->infCte->exped->enderExped;
            $enderReceb = $this->xml->CTe->infCte->receb->enderReceb;

            // Adição na função parse() para extrair dados do tomador
            $dadosTomador = $this->extrairDadosTomador(
                $ide, $rem, $exped, $receb, $dest, 
                $enderRem, $enderExped, $enderReceb, $enderDest
            );
        
            // Dados fiscais (imposto)
            $imp = $this->xml->CTe->infCte->imp;
            $icms = $imp->ICMS;

            // Determinar qual nó do ICMS está sendo utilizado (ICMS00, ICMS20, etc)
            $icmsNode = null;
            $cst = null;

            if (isset($icms->ICMS00)) {
                $icmsNode = $icms->ICMS00;
                $cst = '00';
            } elseif (isset($icms->ICMS20)) {
                $icmsNode = $icms->ICMS20;
                $cst = '20';
            } elseif (isset($icms->ICMS45)) {
                $icmsNode = $icms->ICMS45;
                $cst = '45';
            } elseif (isset($icms->ICMS60)) {
                $icmsNode = $icms->ICMS60;
                $cst = '60';
            } elseif (isset($icms->ICMS90)) {
                $icmsNode = $icms->ICMS90;
                $cst = '90';
            } elseif (isset($icms->ICMSOutraUF)) {
                $icmsNode = $icms->ICMSOutraUF;
                $cst = 'OUTRA_UF';
            } elseif (isset($icms->ICMSSN)) {
                $icmsNode = $icms->ICMSSN;
                $cst = 'SIMPLES';
            }

            $this->data = [
                'chave_acesso' => str_replace('CTe', '', $this->xml->CTe->infCte['Id']),
                'numero' => (string) $ide->nCT,
                'serie' => (string) $ide->serie,
                'data_emissao' => Carbon::parse((string) $ide->dhEmi),
                'status_cte' => $status,            
                
                
                // Dados de referência
                'possui_referencia' => $possuiReferencia,
                'chave_referenciada' => $chaveReferenciada,
                'tipo_referencia' => $tipoReferencia,
                'modal' => (string) $ide->modal,
                'tpServ' => (string) $ide->tpServ,
                'cMunIni' => (string) $ide->cMunIni,
                'xMunIni' => (string) $ide->xMunIni,
                'cMunFim' => (string) $ide->cMunFim,
                'xMunFim' => (string) $ide->xMunFim,
                'UFIni' => (string) $ide->UFIni,
                'UFFim' => (string) $ide->UFFim,
                
              
                // Status do CTe
                'status' => $status,
                'xml_content' => $this->rawXml,

                // Dados do Emitente
                'cnpj_emitente' => (string) $emit->CNPJ,
                'ie_emitente' => (string) $emit->IE,
                'nome_emitente' => (string) $emit->xNome,
                'xFant' => (string) $emit->xFant,
                'logradouro_emitente' => (string) $enderEmit->xLgr,
                'numero_emitente' => (string) $enderEmit->nro,
                'complemento_emitente' => (string) $enderEmit->xCpl,
                'bairro_emitente' => (string) $enderEmit->xBairro,
                'municipio_emitente' => (string) $enderEmit->xMun,
                'cod_municipio_emitente' => (string) $enderEmit->cMun,
                'uf_emitente' => (string) $enderEmit->UF,
                'cep_emitente' => (string) $enderEmit->CEP,

                // Dados do Destinatário
                'cnpj_destinatario' => (string) $dest->CNPJ,
                'ie_destinatario' => (string) $dest->IE,
                'nome_destinatario' => (string) $dest->xNome,
                'xFant_destinatario' => (string) $dest->xFant,
                'logradouro_destinatario' => (string) $enderDest->xLgr,
                'numero_destinatario' => (string) $enderDest->nro,
                'complemento_destinatario' => (string) $enderDest->xCpl,
                'bairro_destinatario' => (string) $enderDest->xBairro,
                'municipio_destinatario' => (string) $enderDest->xMun,
                'cod_municipio_destinatario' => (string) $enderDest->cMun,
                'uf_destinatario' => (string) $enderDest->UF,
                'cep_destinatario' => (string) $enderDest->CEP,

                // Dados do tomador
                'tipo_tomador' => $dadosTomador['tipo_tomador'],
                'cnpj_tomador' => $dadosTomador['cnpj_tomador'],
                'nome_tomador' => $dadosTomador['nome_tomador'],
                'ie_tomador' => $dadosTomador['ie_tomador'],
                'fone_tomador' => $dadosTomador['fone_tomador'],
                'logradouro_tomador' => $dadosTomador['logradouro_tomador'] ?? null,
                'numero_tomador' => $dadosTomador['numero_tomador'] ?? null,
                'complemento_tomador' => $dadosTomador['complemento_tomador'] ?? null,
                'bairro_tomador' => $dadosTomador['bairro_tomador'] ?? null,
                'municipio_tomador' => $dadosTomador['municipio_tomador'] ?? null,
                'cod_municipio_tomador' => $dadosTomador['cod_municipio_tomador'] ?? null,
                'uf_tomador' => $dadosTomador['uf_tomador'] ?? null,
                'cep_tomador' => $dadosTomador['cep_tomador'] ?? null,

                // Dados do remetente (se diferente do emitente/destinatário)

                'cnpj_remetente' => (string) ($rem->CNPJ ?? null),
                'ie_remetente' => (string) ($rem->IE ?? null),
                'nome_remetente' => (string) ($rem->xNome ?? null),
                'xFant_remetente' => (string) ($rem->xFant ?? null),
                'logradouro_remetente' => (string) ($enderRem->xLgr ?? null),
                'numero_remetente' => (string) ($rem->nro ?? null),
                'complemento_remetente' => (string) ($enderRem->xCpl ?? null),
                'bairro_remetente' => (string) ($enderRem->xBairro ?? null),
                'municipio_remetente' => (string) ($enderRem->xMun ?? null),
                'cod_municipio_remetente' => (string) ($enderRem->cMun ?? null),
                'uf_remetente' => (string) ($enderRem->UF ?? null),
                'cep_remetente' => (string) ($enderRem->CEP ?? null),

                // Dados do expedidor (se diferente do emitente/destinatário)
                'cnpj_expedidor' => (string) ($exped->CNPJ ?? null),
                'ie_expedidor' => (string) ($exped->IE ?? null),
                'nome_expedidor' => (string) ($exped->xNome ?? null),
                'fone_expedidor' => (string) ($exped->fone ?? null),
                'xFant_expedidor' => (string) ($exped->xFant ?? null),
                'logradouro_expedidor' => (string) ($enderExped->xLgr ?? null),
                'numero_expedidor' => (string) ($enderExped->nro ?? null),
                'complemento_expedidor' => (string) ($enderExped->xCpl ?? null),
                'bairro_expedidor' => (string) ($enderExped->xBairro ?? null),
                'municipio_expedidor' => (string) ($enderExped->xMun ?? null),
                'cod_municipio_expedidor' => (string) ($enderExped->cMun ?? null),
                'uf_expedidor' => (string) ($enderExped->UF ?? null),
                'cep_expedidor' => (string) ($enderExped->CEP ?? null),

                // Dados do recebedor (se diferente do emitente/destinatário)
                'cnpj_recebedor' => (string) ($receb->CNPJ ?? null),
                'ie_recebedor' => (string) ($receb->IE ?? null),
                'nome_recebedor' => (string) ($receb->xNome ?? null),
                'fone_recebedor' => (string) ($receb->fone ?? null),
                'xFant_recebedor' => (string) ($receb->xFant ?? null),
                'logradouro_recebedor' => (string) ($enderReceb->xLgr ?? null),
                'numero_recebedor' => (string) ($enderReceb->nro ?? null),
                'complemento_recebedor' => (string) ($enderReceb->xCpl ?? null),
                'bairro_recebedor' => (string) ($enderReceb->xBairro ?? null),
                'municipio_recebedor' => (string) ($enderReceb->xMun ?? null),
                'cod_municipio_recebedor' => (string) ($enderReceb->cMun ?? null),
                'uf_recebedor' => (string) ($enderReceb->UF ?? null),
                'cep_recebedor' => (string) ($enderReceb->CEP ?? null),
                
               
                // Dados fiscais
                'cst_icms' => $cst,
                'base_calculo_icms' => isset($icmsNode->vBC) ? (float) $icmsNode->vBC : 0,
                'aliquota_icms' => isset($icmsNode->pICMS) ? (float) $icmsNode->pICMS : 0,
                'valor_icms' => isset($icmsNode->vICMS) ? (float) $icmsNode->vICMS : 0,
                'cfop' => (string) $ide->CFOP,
                'natureza_operacao' => (string) $ide->natOp,

                // Dados da carga
                  // Valores
                'valor_total' => (float) $vPrest->vTPrest,
                'valor_receber' => (float) $vPrest->vRec,                
                'valor_servico' => isset($this->xml->CTe->infCte->vPrest->Comp) ? $this->somarComponentesServico() : $this->data['valor_total'],
                'peso_bruto' => isset($this->xml->CTe->infCte->infCTeNorm->infCarga->infQ) ? $this->extrairPesoBruto() : 0,
            ];

            return $this;
        } catch (Exception $e) {
            Log::error('Erro ao fazer parse do XML de CTe: ' . $e->getMessage());
            throw new Exception('Erro ao processar dados do XML de CTe: ' . $e->getMessage());
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
    public function save(): ConhecimentoTransporteEletronico
    {
        try {
            return DB::transaction(function () {
                $chaveAcesso = $this->data['chave_acesso'];
                
                // Remove campos que serão tratados separadamente
                $possuiReferencia = $this->data['possui_referencia'] ?? false;
                $chaveReferenciada = $this->data['chave_referenciada'] ?? null;
                $tipoReferencia = $this->data['tipo_referencia'] ?? 'NFE';
                
                unset($this->data['possui_referencia']);
                unset($this->data['chave_referenciada']);
                unset($this->data['tipo_referencia']);
                
                // Busca o CTe existente
                $cte = ConhecimentoTransporteEletronico::where('chave_acesso', $chaveAcesso)->first();
                
                // Atualiza ou cria o CTe
                if ($cte) {
                    $cte->update($this->data);
                } else {
                    $cte = ConhecimentoTransporteEletronico::create($this->data);
                }
                
                // Se este CTe faz referência a outro documento, registra esta referência
                if ($possuiReferencia && $chaveReferenciada) {
                    $cte->adicionarReferencia($chaveReferenciada, $tipoReferencia);
                }
                
                // Retorna o CTe
                return $cte;
            });
        } catch (Exception $e) {
            Log::error('Erro ao salvar dados do CTe: ' . $e->getMessage());
            throw new Exception('Erro ao salvar dados do CTe: ' . $e->getMessage());
        }
    }

    /**
     * Retorna os dados extraídos do XML
     */
    public function getData(): array
    {
        return $this->data;
    }

    private function somarComponentesServico(): float
    {
        $total = 0;
        foreach ($this->xml->CTe->infCte->vPrest->Comp as $componente) {
            $total += (float)$componente->vComp;
        }
        return $total;
    }

    private function extrairPesoBruto(): float
    {
        foreach ($this->xml->CTe->infCte->infCTeNorm->infCarga->infQ as $infQ) {
            if ((string)$infQ->tpMed == 'PESO BRUTO' || (string)$infQ->tpMed == 'PESO') {
                return (float)$infQ->qCarga;
            }
        }
        return 0;
    }

    /**
     * Extrai informações do tomador do serviço
     * 
     * @param SimpleXMLElement $ide Nó IDE do XML
     * @param SimpleXMLElement $rem Nó do remetente
     * @param SimpleXMLElement $exped Nó do expedidor
     * @param SimpleXMLElement $receb Nó do recebedor
     * @param SimpleXMLElement $dest Nó do destinatário
     * @param SimpleXMLElement $enderRem Nó do endereço do remetente
     * @param SimpleXMLElement $enderExped Nó do endereço do expedidor
     * @param SimpleXMLElement $enderReceb Nó do endereço do recebedor
     * @param SimpleXMLElement $enderDest Nó do endereço do destinatário
     * 
     * @return array Array com dados do tomador e metadados
     */
    private function extrairDadosTomador($ide, $rem, $exped, $receb, $dest, $enderRem, $enderExped, $enderReceb, $enderDest): array
    {
        $result = [
            'tipo_tomador' => null,
            'cnpj_tomador' => '',
            'ie_tomador' => '',
            'nome_tomador' => '',
            'fone_tomador' => ''
        ];
        
        $toma = null;
        $tomaEnder = null;
        $tomadorIndice = null;
        
        // Verifica qual tipo de tomador está presente no XML
        if (isset($this->xml->CTe->infCte->ide->toma0)) {
            $tomadorIndice = (int)($this->xml->CTe->infCte->ide->toma0);
        } elseif (isset($this->xml->CTe->infCte->ide->toma1)) {
            $tomadorIndice = (int)($this->xml->CTe->infCte->ide->toma1);
        } elseif (isset($this->xml->CTe->infCte->ide->toma2)) {
            $tomadorIndice = (int)($this->xml->CTe->infCte->ide->toma2);
        } elseif (isset($this->xml->CTe->infCte->ide->toma3)) {
            $tomadorIndice = (int)($this->xml->CTe->infCte->ide->toma3);
        } elseif (isset($this->xml->CTe->infCte->ide->toma4)) {
            $tomadorIndice = 4; // Toma4 sempre é tipo 4 (Outros)
        } elseif (isset($this->xml->CTe->infCte->ide->indToma)) {
            // Versão mais recente do CT-e usa indToma
            $tomadorIndice = (int)($this->xml->CTe->infCte->ide->indToma);
        }
        
        // Determina quem é o tomador baseado no índice
        switch ($tomadorIndice) {
            case 0: // Remetente
                $toma = $rem;
                $result['tipo_tomador'] = 'REMETENTE';
                $tomaEnder = $enderRem;
                break;
            case 1: // Expedidor
                $toma = $exped;
                $result['tipo_tomador'] = 'EXPEDIDOR';
                $tomaEnder = $enderExped;
                break;
            case 2: // Recebedor
                $toma = $receb;
                $result['tipo_tomador'] = 'RECEBEDOR';
                $tomaEnder = $enderReceb;
                break;
            case 3: // Destinatário
                $toma = $dest;
                $result['tipo_tomador'] = 'DESTINATARIO';
                $tomaEnder = $enderDest;
                break;
            case 4: // Outros
                // Verificar se é toma3 ou toma4 (depende da versão do CT-e)
                if (isset($this->xml->CTe->infCte->toma3)) {
                    $toma = $this->xml->CTe->infCte->toma3;
                } elseif (isset($this->xml->CTe->infCte->toma4)) {
                    $toma = $this->xml->CTe->infCte->toma4;
                } elseif (isset($this->xml->CTe->infCte->ide->toma4)) {
                    $toma = $this->xml->CTe->infCte->ide->toma4;
                }
                $result['tipo_tomador'] = 'OUTROS';
                $tomaEnder = isset($toma->enderToma) ? $toma->enderToma : null;
                break;
            default:
                $result['tipo_tomador'] = 'DESCONHECIDO';
                break;
        }
        
        // Extrair dados do tomador quando disponíveis
        if ($toma) {
            $result['cnpj_tomador'] = (string) ($toma->CNPJ ?? $toma->CPF ?? '');
            $result['ie_tomador'] = (string) ($toma->IE ?? '');
            $result['nome_tomador'] = (string) ($toma->xNome ?? '');
            $result['fone_tomador'] = (string) ($toma->fone ?? '');
            
            // Se tiver endereço do tomador, adicionar
            if ($tomaEnder) {
                $result['logradouro_tomador'] = (string) ($tomaEnder->xLgr ?? '');
                $result['numero_tomador'] = (string) ($tomaEnder->nro ?? '');
                $result['complemento_tomador'] = (string) ($tomaEnder->xCpl ?? '');
                $result['bairro_tomador'] = (string) ($tomaEnder->xBairro ?? '');
                $result['municipio_tomador'] = (string) ($tomaEnder->xMun ?? '');
                $result['cod_municipio_tomador'] = (string) ($tomaEnder->cMun ?? '');
                $result['uf_tomador'] = (string) ($tomaEnder->UF ?? '');
                $result['cep_tomador'] = (string) ($tomaEnder->CEP ?? '');
                $result['xPais'] = (string) ($tomaEnder->xPais ?? '');
                $result['cPais'] = (string) ($tomaEnder->cPais ?? '');
            }
        }
        
        return $result;
    }
} 