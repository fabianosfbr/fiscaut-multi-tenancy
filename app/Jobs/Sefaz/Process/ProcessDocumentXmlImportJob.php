<?php

namespace App\Jobs\Sefaz\Process;

use App\Services\Sefaz\CteService;
use App\Services\Sefaz\NfeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentXmlImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    public $file;

    public $issuer;

    public function __construct($file, $issuer)
    {
        $this->file = $file;
        $this->issuer = $issuer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $xml = Storage::get($this->file);
        $xmlReader = loadXmlReader($xml);

        // NFe
        if ($xmlReader->value('nfeProc.NFe')->get() || $xmlReader->value('procEventoNFe')->get()) {
            $service = app(NfeService::class)->issuer($this->issuer);
            $service->exec($xmlReader, $xml, 'Importação');
        }
        // CTe
        if ($xmlReader->value('cteProc.CTe')->get()) {
            $service = app(CteService::class)->issuer($this->issuer);
            $service->exec($xmlReader, $xml, 'Importação');
        }

        Storage::delete($this->file);
    }
}
