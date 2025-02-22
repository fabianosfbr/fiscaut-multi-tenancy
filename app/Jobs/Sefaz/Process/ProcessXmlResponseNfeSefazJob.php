<?php

namespace App\Jobs\Sefaz\Process;

use App\Services\Tenant\Sefaz\Traits\HasLogSefaz;
use App\Services\Tenant\Sefaz\Traits\HasNfe;
use App\Traits\HasXmlReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessXmlResponseNfeSefazJob implements ShouldQueue
{
    use Dispatchable, HasLogSefaz, HasNfe, HasXmlReader, InteractsWithQueue, Queueable, SerializesModels;

    public $response;

    public $key;

    public $issuer;

    public $origem;

    public $maxNSU;

    public function __construct($issuer, $response, $key, $origem, $maxNSU)
    {
        $this->response = $response;
        $this->key = $key;
        $this->issuer = $issuer;
        $this->origem = $origem;
        $this->maxNSU = $maxNSU;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reader = loadXmlReader($this->response);

        $numnsu = intval($reader->element('docZip.'.$this->key)->sole()->getAttributes()['NSU']);
        $content = $reader->element('docZip.'.$this->key)->sole()->getContent();
        $xml = gzdecode(base64_decode($content));

        $xmlReader = loadXmlReader($xml);

        $this->registerLogNfeContent($this->issuer, $numnsu, $this->maxNSU, $xml);

        $this->exec($xmlReader, $xml, $this->origem);
    }
}
