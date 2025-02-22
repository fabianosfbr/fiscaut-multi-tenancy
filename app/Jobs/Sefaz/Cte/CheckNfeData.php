<?php

namespace App\Jobs\Sefaz\Cte;

use App\Models\ConhecimentoTransporteEletronico;
use App\Models\NotaFiscalEletronica;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckNfeData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 120000;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ConhecimentoTransporteEletronico $cte
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $chave_nfe = json_decode($this->cte->nfe_chave, true);

        if (! is_null($chave_nfe)) {

            foreach ($chave_nfe as $key => $chave) {

                if (is_string($chave) && $key == 'chave') {

                    $this->proccessedTagged($chave);

                    $this->proccessedMetaData($chave);
                } elseif (is_array($chave)) {

                    foreach ($chave as $value) {

                        $this->proccessedTagged($value);

                        $this->proccessedMetaData($value);
                    }
                }
            }
        }
    }

    public function proccessedTagged($chave)
    {
        $nfe = NotaFiscalEletronica::where('chave', $chave)->where('destinatario_cnpj', $this->cte->destinatario_cnpj)->first();

        if (isset($nfe->tagged)) {

            $this->cte->untag();
            foreach ($nfe->tagged->toArray() as $key => $tagged) {

                $tag = Tag::where('id', $tagged['tag_id'])->first();
                $this->cte->tag($tag, $this->cte->vCTe);
            }
        }
    }

    public function proccessedMetaData($chave)
    {
        $nfe = NotaFiscalEletronica::where('chave', $chave)->first();

        if (isset($nfe)) {

            $meta = [
                'nfe_destinatario_cnpj' => null,
                'nfe_destinatario_razao_social' => null,
                'nfe_emitente_cnpj' => null,
                'nfe_emitente_razao_social' => null,
                'nfe_vICMS' => null,
                'nfe_nNF' => null,
                'nfe_vNfe' => null,
                'nfe_tpNf' => null,
            ];

            $meta['nfe_destinatario_cnpj'] = $nfe->destinatario_cnpj;
            $meta['nfe_destinatario_razao_social'] = $nfe->destinatario_cnpj;
            $meta['nfe_emitente_cnpj'] = $nfe->emitente_cnpj;
            $meta['nfe_emitente_razao_social'] = $nfe->emitente_cnpj;
            $meta['nfe_vICMS'] = $nfe->vICMS;
            $meta['nfe_nNF'] = $nfe->nNF;
            $meta['nfe_vNfe'] = $nfe->vNfe;
            $meta['nfe_tpNf'] = $nfe->tpNf;

            $this->cte->update([
                'metadata' => [
                    $meta,
                ],
            ]);
        }
    }
}
