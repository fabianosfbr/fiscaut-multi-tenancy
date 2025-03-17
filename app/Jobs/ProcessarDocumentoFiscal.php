<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\Tenant\EventoNfe;
use App\Models\Tenant\ResumoNfe;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use NFePHP\NFe\Common\Standardize as NFeStandardize;

class ProcessarDocumentoFiscal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Organization $organization,
        private string $content,
        private string $schema
    ) {}

    public function handle(): void
    {
        try {
            switch (true) {
                case str_contains($this->schema, 'resNFe'):
                    $this->processarResumoNFe();
                    break;

                case str_contains($this->schema, 'procNFe'):
                    $this->processarNFe();
                    break;

                case str_contains($this->schema, 'resEvento'):
                    $this->processarResumoEvento();
                    break;

                case str_contains($this->schema, 'procEventoNFe'):
                    $this->processarEvento();
                    break;

                default:
                    throw new Exception("Schema não reconhecido: {$this->schema}");
            }
        } catch (Exception $e) {
            Log::error("Erro ao processar documento fiscal", [
                'schema' => $this->schema,
                'erro' => $e->getMessage(),
                'organization_id' => $this->organization->id
            ]);

            throw $e; // Permite que o job seja retentado se necessário
        }
    }

    private function processarResumoNFe(): void
    {
        $st = new NFeStandardize();
        $std = $st->toStd($this->content);

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


            EventoNfe::create([
                'organization_id' => $this->organization->id,
                'chave_nfe' => $std->chNFe,
                'tipo_evento' => $std->tpEvento,
                'numero_sequencial' => $std->nSeqEvento,
                'data_evento' => Carbon::parse($std->dhEvento),
                'xml_evento' => $content,
                'protocolo' => $std->nProt ?? null,
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
}
