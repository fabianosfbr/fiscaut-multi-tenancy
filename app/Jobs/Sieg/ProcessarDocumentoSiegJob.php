<?php

namespace App\Jobs\Sieg;

use Exception;
use Illuminate\Bus\Queueable;
use App\Models\Tenant\EventoNfe;
use App\Models\Tenant\EventoCte;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use App\Services\Tenant\Xml\XmlCteReaderService;

class ProcessarDocumentoSiegJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * O número de segundos que o job pode ser processado antes de ser considerado travado.
     *
     * @var int
     */
    public $timeout = 1200; // 20 minutos

    /**
     * O tipo do documento (NFe ou CTe)
     * 
     * @var string
     */
    private string $tipoDocumento;

    /**
     * Cria uma nova instância do job.
     */
    public function __construct(
        private readonly Organization $organization,
        private readonly string $xmlContent,
        private readonly array $params = [],
        private readonly bool $isEvento = false
    ) {
        $this->tipoDocumento = $this->detectarTipoDocumento($this->xmlContent);
    }

    /**
     * Detecta automaticamente o tipo do documento (NFe ou CTe) a partir do conteúdo XML
     */
    private function detectarTipoDocumento(string $xmlContent): string
    {
        // Verifica se contém padrões de CTe no XML
        if (
            str_contains($xmlContent, '<CTe') ||
            str_contains($xmlContent, '<cteProc') ||
            str_contains($xmlContent, 'xmlns="http://www.portalfiscal.inf.br/cte"') ||
            str_contains($xmlContent, '<eventoCTe') ||
            str_contains($xmlContent, '<procEventoCTe') || 
            str_contains($xmlContent, 'chCTe')
        ) {
            return 'CTe';
        }

        // Por padrão, assume que é NFe
        return 'NFe';
    }

    /**
     * Executa o job.
     */
    public function handle(): void
    {
        try {
 
            if ($this->isEvento) {
                if ($this->tipoDocumento === 'CTe') {
                    $this->processarEventoCte();
                } else {
                    $this->processarEvento();
                }
            } else {
                if ($this->tipoDocumento === 'CTe') {
                    $this->processarCTe();
                } else {
                    $this->processarNFe();
                }
            }
        } catch (Exception $e) {
            Log::error("Erro ao processar documento SIEG", [
                'organization_id' => $this->organization->id,
                'erro' => $e->getMessage(),
                'is_evento' => $this->isEvento,
                'tipo_documento' => $this->tipoDocumento,
                'xml' => $this->xmlContent
            ]);

            throw $e; // Relança a exceção para que o job seja retentado
        }
    }

    /**
     * Processa um XML de evento NFe
     */
    private function processarEvento(): void
    {
        try {
            // Utiliza a biblioteca NFePHP para transformar o XML em um array padronizado
            $std = new \NFePHP\NFe\Common\Standardize();
            $stdClass = $std->toStd($this->xmlContent);
            
            // Verifica se o XML é um evento completo (procEventoNFe) ou apenas um evento sem retorno
            if (isset($stdClass->procEventoNFe)) {
                $evento = $stdClass->procEventoNFe;                
                $chaveNFe = $evento->evento->infEvento->chNFe ?? null;
                $tipoEvento = $evento->evento->infEvento->tpEvento ?? null;
                $dataEvento = $evento->evento->infEvento->dhEvento ?? null;
                $detEvento = $evento->evento->infEvento->detEvento ?? null;
                $nSeqEvento = $evento->evento->infEvento->nSeqEvento ?? 0;
                $protocolo = $evento->retEvento->infEvento->nProt ?? null;
                $statusSefaz = $evento->retEvento->infEvento->cStat ?? null;
                $motivo = $evento->retEvento->infEvento->xEvento ?? null;
            } elseif (isset($stdClass->evento)) {
                $evento = $stdClass->evento;
                $retEvento = $stdClass->retEvento;
                $chaveNFe = $evento->infEvento->chNFe ?? null;
                $tipoEvento = $evento->infEvento->tpEvento ?? null;
                $dataEvento = $evento->infEvento->dhEvento ?? null;
                $detEvento = $evento->infEvento->detEvento ?? null;
                $nSeqEvento = $evento->infEvento->nSeqEvento ?? 0;
                $protocolo = $retEvento->infEvento->nProt ?? null;
                $statusSefaz = $retEvento->infEvento->cStat ?? null;
                $motivo = $retEvento->infEvento->xEvento ?? $retEvento->infEvento->xMotivo ?? null;                
            } else {
                Log::warning("Formato de evento NFe não reconhecido", [
                    'xml' => $this->xmlContent,
                    'params' => $this->params
                ]);
                return;
            }
            
            if (!$chaveNFe || !$tipoEvento) {
                Log::warning("Evento NFe sem chave ou tipo evento", [
                    'chave' => $chaveNFe,
                    'tipo' => $tipoEvento,
                    'params' => $this->params
                ]);
                return;
            }
            
            // Converte a data para o formato do banco
            if ($dataEvento) {
                try {
                    $dataEvento = \Carbon\Carbon::parse($dataEvento)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Erro ao converter data do evento NFe", [
                        'data' => $dataEvento,
                        'erro' => $e->getMessage()
                    ]);
                    $dataEvento = now()->format('Y-m-d H:i:s');
                }
            } else {
                $dataEvento = now()->format('Y-m-d H:i:s');
            }
            
            // Verifica se já existe um evento igual
            $eventoExistente = EventoNfe::where('chave_nfe', $chaveNFe)
                ->where('tipo_evento', $tipoEvento)
                ->where('numero_sequencial', $nSeqEvento)
                ->first();
                
            if ($eventoExistente) {
                // Atualiza o evento existente
                $eventoExistente->update([
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $this->xmlContent,
                ]);
            } else {
                // Cria um novo evento
                EventoNfe::create([
                    'organization_id' => $this->organization->id,
                    'chave_nfe' => $chaveNFe,
                    'tipo_evento' => $tipoEvento,
                    'numero_sequencial' => $nSeqEvento,
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $this->xmlContent,
                ]);
            }
        } catch (Exception $e) {
            Log::error("Erro ao processar evento NFe", [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'params' => $this->params
            ]);
            throw $e;
        }
    }

    /**
     * Processa um XML de evento CTe
     */
    private function processarEventoCte(): void
    {
        try {
            // Utiliza a biblioteca NFePHP para transformar o XML em um array padronizado
            $std = new \NFePHP\CTe\Common\Standardize();
            $stdClass = $std->toStd($this->xmlContent);
            
            // Verifica se o XML é um evento completo (procEventoCTe) ou apenas um evento sem retorno
            if (isset($stdClass->eventoCTe) || isset($stdClass->procEventoCTe)) {
                // Extrai os dados do evento conforme a estrutura
                $evento = $stdClass->eventoCTe ?? $stdClass->procEventoCTe->eventoCTe ?? null;
                $retEvento = $stdClass->retEventoCTe ?? $stdClass->procEventoCTe->retEventoCTe ?? null;
                
                if (!$evento || !$retEvento) {
                    Log::warning("Formato de evento CTe não reconhecido", [
                        'params' => $this->params,
                        'xml' => $this->xmlContent,
                    ]);
                    return;
                }
                
                $chaveCTe = $evento->infEvento->chCTe ?? null;
                $tipoEvento = $evento->infEvento->tpEvento ?? null;
                $dataEvento = $evento->infEvento->dhEvento ?? null;
                $nSeqEvento = $evento->infEvento->nSeqEvento ?? 0;
                $protocolo = $retEvento->infEvento->nProt ?? null;
                $statusSefaz = $retEvento->infEvento->cStat ?? null;
                $motivo = $retEvento->infEvento->xEvento ?? $retEvento->infEvento->xMotivo ?? null;
            } else {
                Log::warning("Formato de evento CTe não reconhecido", [
                    'xml' => $this->xmlContent,
                    'params' => $this->params
                ]);
                return;
            }
            
            if (!$chaveCTe || !$tipoEvento) {
                Log::warning("Evento CTe sem chave ou tipo evento", [
                    'chave' => $chaveCTe,
                    'tipo' => $tipoEvento,
                    'params' => $this->params
                ]);
                return;
            }
            
            // Converte a data para o formato do banco
            if ($dataEvento) {
                try {
                    $dataEvento = \Carbon\Carbon::parse($dataEvento)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Erro ao converter data do evento CTe", [
                        'data' => $dataEvento,
                        'erro' => $e->getMessage()
                    ]);
                    $dataEvento = now()->format('Y-m-d H:i:s');
                }
            } else {
                $dataEvento = now()->format('Y-m-d H:i:s');
            }
            
            // Verifica se já existe um evento igual
            $eventoExistente = EventoCte::where('chave_cte', $chaveCTe)
                ->where('tipo_evento', $tipoEvento)
                ->where('numero_sequencial', $nSeqEvento)
                ->first();
                
            if ($eventoExistente) {
                // Atualiza o evento existente
                $eventoExistente->update([
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $this->xmlContent,
                ]);
            } else {
                // Cria um novo evento
                EventoCte::create([
                    'organization_id' => $this->organization->id,
                    'chave_cte' => $chaveCTe,
                    'tipo_evento' => $tipoEvento,
                    'numero_sequencial' => $nSeqEvento,
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $this->xmlContent,
                ]);
            }
        } catch (Exception $e) {
            Log::error("Erro ao processar evento CTe", [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'params' => $this->params
            ]);
            throw $e;
        }
    }

    /**
     * Processa a NFe completa
     */
    private function processarNFe(): void
    {
        try {
            $xmlReader = new XmlNfeReaderService();
            $xmlReader->loadXml($this->xmlContent)
                ->parse()
                ->setOrigem('SIEG')
                ->save();
        } catch (\Exception $e) {
            Log::error('Erro ao processar NFe da Sieg: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o CTe completo
     */
    private function processarCTe(): void
    {
        try {
            $xmlReader = new XmlCteReaderService();
            $xmlReader->loadXml($this->xmlContent)
                ->parse()
                ->setOrigem('SIEG')
                ->save();
            
            Log::info('CTe da Sieg processado com sucesso', [
                'organization_id' => $this->organization->id
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar CTe da Sieg: ' . $e->getMessage());
            throw $e;
        }
    }
} 