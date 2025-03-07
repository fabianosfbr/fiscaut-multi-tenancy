<?php

namespace App\Services\Tenant\Xml;

use Exception;
use Carbon\Carbon;
use SimpleXMLElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\ConhecimentoTransporte;
use App\Models\Tenant\ConhecimentoTransporteEletronico;

class XmlCteReaderService
{
    private ?SimpleXMLElement $xml = null;
    private array $data = [];
    private string $rawXml;

    /**
     * Carrega o conteúdo do XML
     */
    public function loadXml(string $xmlContent): self
    {
       
        try {
            $this->rawXml = $xmlContent;
            $this->xml = new SimpleXMLElement($xmlContent);
            
            // Registra os namespaces necessários para CTe
            $this->xml->registerXPathNamespace('cte', 'http://www.portalfiscal.inf.br/cte');
            
            return $this;
        } catch (Exception $e) {
            Log::error('Erro ao carregar XML do CTe: ' . $e->getMessage());
            throw new Exception("XML de CTe inválido ou mal formatado: {$e->getMessage()}");
        }
    }

    /**
     * Extrai os dados do XML
     */
    public function parse(): self
    {
        if (!$this->xml) {
            throw new Exception('XML não foi carregado');
        }

        try {
            // Registra os namespaces necessários para CTe
            $this->xml->registerXPathNamespace('cte', 'http://www.portalfiscal.inf.br/cte');

            // Verifica se existe o elemento CTe
            if (!isset($this->xml->CTe) || !isset($this->xml->CTe->infCte)) {
                throw new Exception('Estrutura do XML do CTe inválida: CTe ou infCte não encontrado');
            }

            // Extrai informações básicas do CTe usando o caminho correto
            $ide = $this->xml->CTe->infCte->ide;
            $emit = $this->xml->CTe->infCte->emit;
            $dest = $this->xml->CTe->infCte->dest;
            $rem = $this->xml->CTe->infCte->rem;
            $vPrest = $this->xml->CTe->infCte->vPrest;
            $imp = $this->xml->CTe->infCte->imp;
            
            // O infCarga pode estar em diferentes locais dependendo do tipo de CTe
            $infCarga = $this->xml->CTe->infCte->infCTeNorm->infCarga ?? null;

            // Extrai o status do CTe do protocolo (se existir)
            $status = 'EMITIDO'; // Status padrão
            if (isset($this->xml->protCTe)) {
                $cStat = (string) $this->xml->protCTe->infProt->cStat;
                switch ($cStat) {
                    case '100': // Autorizado o uso do CT-e
                    case '150': // Autorizado o uso do CT-e, autorização fora de prazo
                        $status = 'AUTORIZADO';
                        break;
                    case '101': // Cancelamento de CT-e homologado
                    case '151': // Cancelamento de CT-e homologado fora de prazo
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

            // Monta o array de dados com verificações de null
            $this->data = [
                'chave_acesso' => str_replace('CTe', '', (string) $this->xml->CTe->infCte['Id']),
                'numero' => (string) $ide->nCT,
                'serie' => (string) $ide->serie,
                'data_emissao' => Carbon::parse((string) $ide->dhEmi),
                'data_entrada' => Carbon::now(),
                
                // Emitente
                'cnpj_emitente' => (string) $emit->CNPJ,
                'nome_emitente' => (string) $emit->xNome,
                'ie_emitente' => (string) $emit->IE,
                
                // Destinatário
                'cnpj_destinatario' => (string) ($dest->CNPJ ?? $dest->CPF ?? ''),
                'nome_destinatario' => (string) $dest->xNome,
                'ie_destinatario' => (string) ($dest->IE ?? ''),
                
                // Remetente
                'cnpj_remetente' => (string) ($rem->CNPJ ?? $rem->CPF ?? ''),
                'nome_remetente' => (string) $rem->xNome,
                'ie_remetente' => (string) ($rem->IE ?? ''),
                
                // Valores
                'valor_total' => (float) ($vPrest->vTPrest ?? 0),
                'valor_receber' => (float) ($vPrest->vRec ?? 0),
                'valor_servico' => (float) ($vPrest->vTPrest ?? 0),
                
                // ICMS - com tratamento para diferentes tipos de tributação
                'valor_icms' => $this->extrairValorICMS($imp->ICMS),
                'base_calculo_icms' => $this->extrairBaseCalculoICMS($imp->ICMS),
                'aliquota_icms' => $this->extrairAliquotaICMS($imp->ICMS),
                
                // Informações do Transporte
                'modal' => (string) $ide->modal,
                'tipo_servico' => (string) $ide->tpServ,
                'quantidade_carga' => (int) ($infCarga->qCarga ?? 0),
                'peso_bruto' => $this->extrairPesoCarga($infCarga, 'PESO BRUTO'),
                'peso_base_calculo' => $this->extrairPesoCarga($infCarga, 'PESO BC'),
                'peso_aferido' => $this->extrairPesoCarga($infCarga, 'PESO AFERIDO'),
                'unidade_medida' => $this->extrairUnidadeMedida($infCarga),
                
                // Status e Controle
                'status_cte' => $status,
                'status_manifestacao' => 'PENDENTE',
                'origem' => 'IMPORTADO',
                'xml_content' => $this->rawXml,
            ];

            return $this;
        } catch (Exception $e) {
            Log::error('Erro ao fazer parse do XML do CTe: ' . $e->getMessage());
            throw new Exception('Erro ao processar dados do XML do CTe: ' . $e->getMessage());
        }
    }

    /**
     * Extrai o valor do ICMS considerando diferentes tipos de tributação
     */
    private function extrairValorICMS($icms): float
    {
        $tipos = ['ICMS00', 'ICMS20', 'ICMS45', 'ICMS60', 'ICMS90'];
        foreach ($tipos as $tipo) {
            if (isset($icms->$tipo) && isset($icms->$tipo->vICMS)) {
                return (float) $icms->$tipo->vICMS;
            }
        }
        return 0;
    }

    /**
     * Extrai a base de cálculo do ICMS
     */
    private function extrairBaseCalculoICMS($icms): float
    {
        $tipos = ['ICMS00', 'ICMS20', 'ICMS90'];
        foreach ($tipos as $tipo) {
            if (isset($icms->$tipo) && isset($icms->$tipo->vBC)) {
                return (float) $icms->$tipo->vBC;
            }
        }
        return 0;
    }

    /**
     * Extrai a alíquota do ICMS
     */
    private function extrairAliquotaICMS($icms): float
    {
        $tipos = ['ICMS00', 'ICMS20', 'ICMS90'];
        foreach ($tipos as $tipo) {
            if (isset($icms->$tipo) && isset($icms->$tipo->pICMS)) {
                return (float) $icms->$tipo->pICMS;
            }
        }
        return 0;
    }

    /**
     * Extrai o peso da carga conforme o tipo especificado
     */
    private function extrairPesoCarga(?SimpleXMLElement $infCarga, string $tipo): float
    {
        if (!$infCarga || !isset($infCarga->infQ)) {
            return 0;
        }

        foreach ($infCarga->infQ as $infQ) {
            if ((string) $infQ->tpMed == $tipo) {
                return (float) $infQ->qCarga;
            }
        }

        return 0;
    }

    /**
     * Extrai a unidade de medida da primeira informação de quantidade
     */
    private function extrairUnidadeMedida(?SimpleXMLElement $infCarga): string
    {
        if (!$infCarga || !isset($infCarga->infQ[0])) {
            return '';
        }

        return (string) $infCarga->infQ[0]->tpMed;
    }

    /**
     * Retorna os dados extraídos do XML
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Salva ou atualiza os dados no banco
     */
    public function save(): ConhecimentoTransporteEletronico
    {
        try {
            return DB::transaction(function () {
                $chaveAcesso = $this->data['chave_acesso'];
                
                // Busca CTe existente
                $cte = ConhecimentoTransporteEletronico::where('chave_acesso', $chaveAcesso)->first();

                if ($cte) {
                    // Atualiza apenas os campos que podem ser modificados
                    $camposAtualizaveis = [
                        'status_cte',
                        'status_manifestacao',
                        'origem',
                        'xml_content',
                    ];

                    $dadosAtualizacao = array_intersect_key(
                        $this->data,
                        array_flip($camposAtualizaveis)
                    );

                    // Se o status atual for CANCELADO, não permite alteração para AUTORIZADO
                    if ($cte->status_cte === 'CANCELADO' && $dadosAtualizacao['status_cte'] === 'AUTORIZADO') {
                        throw new Exception("Não é possível alterar o status de um CTe CANCELADO para AUTORIZADO");
                    }

                    // Atualiza o CTe
                    $cte->update($dadosAtualizacao);

                    // Registra o histórico de alteração
                    $this->registrarHistoricoAlteracao($cte, $dadosAtualizacao);

                    return $cte;
                }

                // Se não existir, cria um novo CTe
                return ConhecimentoTransporteEletronico::create($this->data);
            });
        } catch (Exception $e) {
            Log::error('Erro ao salvar CTe: ' . $e->getMessage());
            throw new Exception('Erro ao salvar dados do CTe no banco: ' . $e->getMessage());
        }
    }

    /**
     * Registra o histórico de alteração do CTe
     */
    private function registrarHistoricoAlteracao(ConhecimentoTransporteEletronico $cte, array $dadosAtualizados): void
    {
        $cte->historicos()->create([
            'data_alteracao' => now(),
            'campos_alterados' => $dadosAtualizados,
            'usuario_id' => auth()->id() ?? null,
        ]);
    }
} 