<?php

namespace App\Jobs\Sefaz;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\LogSefazCteContent;
use App\Services\Sefaz\CteService;
use App\Models\NotaFiscalEletronica;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\ConhecimentoTransporteEletronico;

class MonitoraCteMetadataFaltanteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    public $issuer;

    public function __construct($issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ConhecimentoTransporteEletronico::where('tomador_cnpj', $this->issuer->cnpj)
            ->whereNull('metadata')
            ->whereNotNull('nfe_chave')
            ->where('data_emissao', '>', now()->subDays(40)->endOfDay())
            ->orderBy('id', 'desc')
            ->chunk(500, function ($ctes) {

                foreach ($ctes as $cte) {

                    $chave_nfe = json_decode($cte->nfe_chave, true);

                    if (isset($chave_nfe) && is_array($chave_nfe)) {


                        foreach ($chave_nfe as $chaveNfe) {

                            if (is_array($chaveNfe)) {
                                $chave = $chaveNfe['chave'];
                            } else {
                                $chave = $chaveNfe;
                            }

                            $meta = [
                                'nfe_destinatario_cnpj' => null,
                                'nfe_emitente_cnpj' => null,
                                'nfe_vICMS' => null,
                                'nfe_tpNf' => null,
                            ];

                            $nfe = NotaFiscalEletronica::where('chave', $chave)->first();

                            if (isset($nfe)) {

                                $meta['nfe_destinatario_cnpj'] = $nfe->destinatario_cnpj;
                                $meta['nfe_emitente_cnpj'] = $nfe->emitente_cnpj;
                                $meta['nfe_vICMS'] = $nfe->vICMS;
                                $meta['nfe_tpNf'] = $nfe->tpNf;

                                $cte->update([
                                    'metadata' => [
                                        $meta,
                                    ]
                                ]);
                            }
                        }
                    }
                }
            });
    }
}
