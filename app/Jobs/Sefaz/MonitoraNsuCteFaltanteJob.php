<?php

namespace App\Jobs\Sefaz;

use App\Models\LogSefazCteContent;
use App\Services\Sefaz\CteService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitoraNsuCteFaltanteJob implements ShouldQueue
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
        $date = Carbon::now()->subDays(20);

        $max = LogSefazCteContent::where('issuer_id', $this->issuer->id)
            ->whereDate('created_at', '>=', $date)
            ->max('max_nsu');

        $min = LogSefazCteContent::where('issuer_id', $this->issuer->id)
            ->whereDate('created_at', '>=', $date)
            ->min('nsu');

        if (isset($max) and isset($min)) {
            $nsus = LogSefazCteContent::where('issuer_id', $this->issuer->id)
                ->whereBetween('nsu', [$min, $max])
                ->get()->pluck('nsu', 'id');

            for ($nsu = $min; $nsu < $max; $nsu++) {
                if (!$nsus->contains($nsu)) {
                    $service = app(CteService::class);
                    $service->issuer($this->issuer);
                    $service->buscarDocumentosFiscaisPorNsu($nsu);
                    break;
                }
            }
        }
    }
}
