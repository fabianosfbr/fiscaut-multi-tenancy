<?php

namespace App\Jobs\Sefaz;

use App\Services\Sefaz\NfeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ManifestaCienciaDaOperacaoSefazJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    private $issuer;

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

        $service->manifestaCienciaDaOperacao();

        Log::info('Manifesta Ciência da Operação Automática - Empresa:  '.explode(':', $this->issuer->razao_social)[0].' em: '.date('d-m-y H:i:s'));
    }
}
