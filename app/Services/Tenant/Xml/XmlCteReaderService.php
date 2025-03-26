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
            $dest = $this->xml->CTe->infCte->dest;
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

            $this->data = [
                'chave_acesso' => str_replace('CTe', '', $this->xml->CTe->infCte['Id']),
                'numero' => (string) $ide->nCT,
                'serie' => (string) $ide->serie,
                'data_emissao' => Carbon::parse((string) $ide->dhEmi),
                'cnpj_emitente' => (string) $emit->CNPJ,
                'ie_emitente' => (string) ($emit->IE ?? ''),
                'nome_emitente' => (string) $emit->xNome,
                'cnpj_destinatario' => (string) ($dest->CNPJ ?? $dest->CPF),
                'ie_destinatario' => (string) ($dest->IE ?? ''),
                'nome_destinatario' => (string) $dest->xNome,
                
                // Dados de referência
                'possui_referencia' => $possuiReferencia,
                'chave_referenciada' => $chaveReferenciada,
                'tipo_referencia' => $tipoReferencia,
                
                // Valores
                'valor_total' => (float) $vPrest->vTPrest,
                'valor_receber' => (float) $vPrest->vRec,
                
                // Status do CTe
                'status' => $status,
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
} 