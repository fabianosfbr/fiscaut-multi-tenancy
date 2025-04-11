<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\Tenant\EventoNfe;
use App\Models\Tenant\EventoCte;
use App\Models\Tenant\ResumoNfe;
use App\Models\Tenant\ResumoCte;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use App\Services\Tenant\Xml\XmlCteReaderService;
use NFePHP\NFe\Common\Standardize as NFeStandardize;
use NFePHP\CTe\Common\Standardize as CTeStandardize;

class ProcessarDocumentoFiscal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Organization $organization,
        private string $content,
        private string $schema,
        private string $tipoDocumento = 'NFe'
    ) {}

    public function handle(): void
    {
        try {
            // Processa com base no tipo de documento
            if ($this->tipoDocumento === 'CTe') {
                $this->processarDocumentoCTe();
            } else {
                $this->processarDocumentoNFe();
            }
        } catch (Exception $e) {
            Log::error("Erro ao processar documento fiscal", [
                'schema' => $this->schema,
                'tipo' => $this->tipoDocumento,
                'erro' => $e->getMessage(),
                'organization_id' => $this->organization->id
            ]);

            throw $e; // Permite que o job seja retentado se necessário
        }
    }

    /**
     * Processa documentos do tipo NFe
     */
    private function processarDocumentoNFe(): void
    {
        switch (true) {
            case str_contains($this->schema, 'resNFe'):
                $this->processarResumoNFe($this->content);
                break;

            case str_contains($this->schema, 'procNFe'):
                $this->processarNFe($this->content);
                break;

            case str_contains($this->schema, 'resEvento'):
                $this->processarResumoEvento($this->content);
                break;

            case str_contains($this->schema, 'procEventoNFe'):
                $this->processarEvento($this->content);
                break;

            default:
                throw new Exception("Schema NFe não reconhecido: {$this->schema}");
        }
    }

    /**
     * Processa documentos do tipo CTe
     */
    private function processarDocumentoCTe(): void
    {
        switch (true) {  
            case str_contains($this->schema, 'procCTe') || str_contains($this->schema, 'cteProc'):
                $this->processarCTe($this->content);
                break;

            case str_contains($this->schema, 'procEventoCTe'):
                $this->processarEventoCTe($this->content);
                break;

            default:
                throw new Exception("Schema CTe não reconhecido: {$this->schema}");
        }
    }

    private function processarResumoNFe($content): void
    {
        $st = new NFeStandardize();
        $std = $st->toStd($content);

        ResumoNfe::updateOrCreate(
            ['chave' => $std->chNFe],
            [
                'organization_id' => $this->organization->id,
                'cnpj_emitente' => $std->CNPJ,
                'nome_emitente' => $std->xNome,
                'ie_emitente' => $std->IE,
                'data_emissao' => Carbon::parse($std->dhEmi),
                'valor_total' => $std->vNF,
                'situacao' => $std->cSitNFe,
                'xml_resumo' => $this->content,
                'necessita_manifestacao' => true
            ]
        );
    }

    /**
     * Processa a NFe completa
     */
    private function processarNFe(string $content): void
    {
        try {
            $xmlReader = new XmlNfeReaderService();
            $xmlReader->loadXml($content)
                ->parse()
                ->setOrigem('SEFAZ')
                ->save();
        } catch (Exception $e) {
            Log::error('Erro ao processar NFe: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o resumo do evento
     */
    private function processarResumoEvento(string $content): void
    {
        try {
            $st = new NFeStandardize();
            $std = $st->toStd($content);

            EventoNfe::updateOrCreate([
                'organization_id' => $this->organization->id,
                'chave_nfe' => $std->chNFe,
                'tipo_evento' => $std->tpEvento,
                'numero_sequencial' => $std->nSeqEvento,
            ],
            [
                'organization_id' => $this->organization->id,
                'chave_nfe' => $std->chNFe,
                'tipo_evento' => $std->tpEvento,
                'numero_sequencial' => $std->nSeqEvento,
                'data_evento' => Carbon::parse($std->dhEvento),
                'motivo' => $std->xEvento,
                'xml_evento' => $content,
                'protocolo' => $std->nProt ?? null
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao processar resumo evento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o evento completo
     */
    private function processarEvento(string $content): void
    {
        try {
            $st = new NFeStandardize();
            $std = $st->toStd($content);

            // Acessa os dados do evento dentro da estrutura correta
            $evento = $std->evento;
            $retEvento = $std->retEvento;

            EventoNfe::updateOrCreate(
                [
                    'organization_id' => $this->organization->id,
                    'chave_nfe' => $evento->infEvento->chNFe,
                    'tipo_evento' => $evento->infEvento->tpEvento,
                    'numero_sequencial' => $evento->infEvento->nSeqEvento
                ],
                [
                    'organization_id' => $this->organization->id,
                    'data_evento' => Carbon::parse($evento->infEvento->dhEvento),
                    'xml_evento' => $content,
                    'protocolo' => $retEvento->infEvento->nProt,
                    'status_sefaz' => $retEvento->infEvento->cStat,
                    'motivo' => $retEvento->infEvento->xMotivo
                ]
            );
        } catch (Exception $e) {
            Log::error('Erro ao processar evento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o CTe completo
     */
    private function processarCTe(string $content): void
    {
        try {
            $xmlReader = new XmlCteReaderService();
            $xmlReader->loadXml($content)
                ->parse()
                ->setOrigem('SEFAZ')
                ->save();
                
            Log::info('CTe processado com sucesso', [
                'organization_id' => $this->organization->id
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao processar CTe: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processa o evento CTe
     */
    private function processarEventoCTe(string $content): void
    {
        try {
            $st = new CTeStandardize();
            $std = $st->toStd($content);

            // Verifica se a estrutura do evento CTe é semelhante à NFe
            // Pode ser necessário ajustar conforme a estrutura real retornada
            $evento = $std->eventoCTe ?? null;
            $retEvento = $std->retEventoCTe ?? null;
            
            if ($evento && $retEvento) {
                // Evento no formato completo
                $chave = $evento->infEvento->chCTe ?? null;
                $tipoEvento = $retEvento->infEvento->tpEvento ?? null;
                $nSeqEvento = $retEvento->infEvento->nSeqEvento ?? 1;
                $dataEvento = $evento->infEvento->dhEvento ?? null;
                $protocolo = $retEvento->infEvento->nProt ?? null;
                $status = $retEvento->infEvento->cStat ?? null;
                $motivo = $retEvento->infEvento->xEvento ?? null;
            } else {
                // Possível formato resumido ou diferente
                $chave = $std->chCTe ?? null;
                $tipoEvento = $std->tpEvento ?? null;
                $nSeqEvento = $std->nSeqEvento ?? 1;
                $dataEvento = $std->dhEvento ?? null;
                $protocolo = $std->nProt ?? null;
                $status = null;
                $motivo = null;
            }

            if (!$chave) {
                throw new Exception('Não foi possível extrair a chave do evento CTe');
            }

            // Salva o evento usando o modelo EventoCte específico para CTe
            EventoCte::create([
                'organization_id' => $this->organization->id,
                'chave_cte' => $chave,
                'tipo_evento' => $tipoEvento,
                'numero_sequencial' => $nSeqEvento,
                'data_evento' => $dataEvento ? Carbon::parse($dataEvento) : now(),
                'xml_evento' => $content,
                'protocolo' => $protocolo,
                'status_sefaz' => $status,
                'motivo' => $motivo
            ]);
            
            Log::info('Evento CTe processado com sucesso', [
                'chave' => $chave,
                'tipo' => $tipoEvento
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao processar evento CTe: ' . $e->getMessage());
            throw $e;
        }
    }

   
}
