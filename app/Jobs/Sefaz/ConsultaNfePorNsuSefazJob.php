<?php

namespace App\Jobs\Sefaz;

use App\Services\Sefaz\NfeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConsultaNfePorNsuSefazJob implements ShouldQueue
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
        $service = app(NfeService::class);
        $service->issuer($this->issuer);
        $service->buscarDocumentosFiscaisPorNsu($this->issuer->last_consult_nsu_nfe);

        $this->issuer->update([
            'last_consult_nsu_nfe' => $this->issuer->last_consult_nsu_nfe + 1,
        ]);
    }
}
