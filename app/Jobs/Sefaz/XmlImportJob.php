<?php

namespace App\Jobs\Sefaz;

use App\Jobs\Sefaz\Process\ProcessDocumentXmlImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class XmlImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    private $filesData;

    private $issuer;

    public function __construct($filesData, $issuer)
    {
        $this->filesData = $filesData;
        $this->issuer = $issuer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->filesData as $file) {
            $name = $file['name'];
            $extension = $file['extension'];
            $path = $file['path'];
            $directory = $file['directory'];

            if ($extension == 'zip') {
                $zip = new ZipArchive;
                if ($zip->open(storage_path().'/app/'.$path)) {
                    $zip->extractTo(storage_path().'/app/'.$directory.'/unzip/');
                    $zip->close();

                    Storage::delete($path);

                    $files = Storage::allFiles($directory.'/unzip/');

                    foreach ($files as $file) {
                        ProcessDocumentXmlImportJob::dispatch($file, $this->issuer)->onQueue('low');
                    }
                }
            } elseif ($extension == 'xml') {
                ProcessDocumentXmlImportJob::dispatch($path, $this->issuer)->onQueue('low');
            }
        }

    }
}
