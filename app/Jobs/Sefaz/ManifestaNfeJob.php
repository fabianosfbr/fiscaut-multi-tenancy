<?php

namespace App\Jobs\Sefaz;

use App\Models\Issuer;
use App\Models\LogSefazManifestoEvent;
use App\Services\Sefaz\NfeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NFePHP\NFe\Common\Standardize;

class ManifestaNfeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Model $record,
        public array $data,
        public int $issuerId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $justificativa = array_key_exists('justificativa', $this->data) ? $this->data['justificativa'] : '';

        $issuer = Issuer::find($this->issuerId);
        $service = app(NfeService::class)->issuer($issuer);

        $nfe = $this->record;
        $response = $service->sefazManifesta($nfe->chave, $this->data['status_manifestacao'], $justificativa);
        $st = new Standardize($response);
        $std = $st->toStd();

        LogSefazManifestoEvent::create([
            'issuer_id' => $issuer->id,
            'chave' => $nfe->chave,
            'type' => 'nfe',
            'tpEvento' => $std->retEvento->infEvento->tpEvento,
            'cStat' => $std->cStat,
            'xMotivo' => $std->xMotivo,
            'justificativa' => $justificativa,
            'infEvento_cStat' => $std->retEvento->infEvento->cStat,
            'infEvento_xMotivo' => $std->retEvento->infEvento->xMotivo,
            'xml' => $response,
        ]);

        $nfe->update([
            'data_manifesto' => date('Y-m-d H:i:s'),
            'data_entrada' => isset($data['data_entrada']) ? str_replace('T', ' ', $data['data_entrada']) : $nfe->data_entrada,
        ]);

        if ($std->cStat == '128') {
            $nfe->update(['status_manifestacao' => $this->data['status_manifestacao']]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Manifestarção da NFe')
            ->log('Manifestarção da NFe chave: '.$this->record->chave);
    }
}
