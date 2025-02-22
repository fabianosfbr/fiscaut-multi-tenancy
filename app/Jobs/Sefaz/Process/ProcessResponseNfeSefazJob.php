<?php

namespace App\Jobs\Sefaz\Process;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessResponseNfeSefazJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $response;

    public $issuer;

    public $origem;

    public function __construct($issuer, $response, $origem)
    {

        $this->response = $response;
        $this->issuer = $issuer;
        $this->origem = $origem;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reader = loadXmlReader($this->response);

        $maxNSU = $reader->value('maxNSU')->sole();
        foreach ($reader->value('docZip')->get() as $key => $doc) {

            // Cada doc vira um job de processamento
            ProcessXmlResponseNfeSefazJob::dispatch($this->issuer, $this->response, $key, $this->origem, $maxNSU)
                ->onQueue('low');
        }
    }
}
