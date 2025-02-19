<?php

namespace App\Traits;

use App\Models\Tenant\Tag;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tenant\LogSefazNfeEvent;
use Illuminate\Support\Facades\DB;
use NFePHP\NFe\Common\Standardize;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant\ConhecimentoTransporteEletronico;

trait NfeHelper
{

    public function gerarCartaCorrecao($id)
    {

        $event = LogSefazNfeEvent::find($id);

        if (!isset($event)) {
            return null;
        }

        $reader = loadXmlReader($event->xml);

        $xml = $reader->values();


        $filename =  Str::random(8) . '.pdf';
        $creditos = 'Impresso em ' . date('d/m/Y') . ' as ' . date('H:i:s') . '  ' . env('APP_FOOTER_CREDITS_DANFE');

        dd($xml);
        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
            ->loadView('pdfs.evento-carta-correcao', [
                'event' => $event,
                'xml' => $xml,
                'creditos' => $creditos,
            ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename . '.pdf');
    }


    public function gerarCartaManifestacao($record)
    {

        $event = DB::table('log_sefaz_manifesto_event')
            ->where('type', 'nfe')
            ->where('chave', $record->chave)
            ->where('tpEvento', $record->status_manifestacao)
            ->first();

        if (!$event) {
            return null;
        }

        $filename =  Str::random(8) . '.pdf';
        $creditos = 'Impresso em ' . date('d/m/Y') . ' as ' . date('H:i:s') . '  ' . env('APP_FOOTER_CREDITS_DANFE');
        $std =  new Standardize($event->xml);
        $xml = xmlNfeToStd($event->xml);
        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])
            ->loadView('pdfs.evento-manifesto-nfe', [
                'event' => $event,
                'xml' => $xml,
                'creditos' => $creditos,
            ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename . '.pdf');
    }

    public function aplicarEtiqueta($nfeId, $cnpjEmitente, $tagId)
    {
        $tag = Tag::find($tagId);
        $nfe = NotaFiscalEletronica::where('id', $nfeId)
            ->select(['id', 'vNfe'])
            ->first();

        $this->aplicarTagNoNfe($nfe, $tag);

       // $this->aplicarTagNosCtes($nfe, $tag);

        Cache::forget('tagging_summary-' . $cnpjEmitente);
    }


    private function aplicarTagNoNfe($nfe, $tag)
    {
        $nfe->tag($tag, $nfe->vNfe);
    }

    private function aplicarTagNosCtes($nfe, $tag)
    {
        $ctes = ConhecimentoTransporteEletronico::whereJsonContains('nfe_chave', ['chave' => $nfe->chave])
            ->where('tomador_cnpj', $nfe->destinatario_cnpj)
            ->get();

        $ctes->each(function ($cte) use ($tag) {
            $cte->untag();
            $cte->tag($tag, $cte->vCTe);
        });
    }
}
