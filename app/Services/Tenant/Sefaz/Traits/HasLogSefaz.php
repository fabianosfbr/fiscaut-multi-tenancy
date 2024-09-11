<?php

namespace App\Services\Tenant\Sefaz\Traits;



use Illuminate\Support\Facades\Log;
use App\Models\Tenant\LogSefazCteEvent;
use App\Models\Tenant\LogSefazNfeEvent;
use App\Models\Tenant\LogSefazCteContent;
use App\Models\Tenant\LogSefazNfeContent;
use App\Models\Tenant\NotaFiscalEletronica;

trait HasLogSefaz
{
    public function registerLogCteContent($organization, $numnsu, $maxNSU, $xml)
    {
        $logContent = LogSefazCteContent::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'nsu' => $numnsu,
            ],
            [
                'organization_id' => $organization->id,
                'nsu' => $numnsu,
                'max_nsu' => $maxNSU,
                'xml' => $xml,
            ]
        );

        Log::notice('CTe NSU consulta SEFAZ: ' . $numnsu . ' maxnsu: ' . $maxNSU . ' Empresa: ' . $organization->razao_social);

        return $logContent;
    }

    public function registerLogNfeContent($organization, $numnsu, $maxNSU, $xml)
    {
        $logContent = LogSefazNfeContent::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'nsu' => $numnsu,
            ],
            [
                'organization_id' => $organization->id,
                'nsu' => $numnsu,
                'max_nsu' => $maxNSU,
                'xml' => $xml,
            ]
        );

        Log::notice('NFe NSU consulta SEFAZ: ' . $numnsu . ' maxnsu: ' . $maxNSU . ' Empresa: ' . $organization->razao_social);

        return $logContent;
    }

    public function registerLogCteEvent($organization, $xml, $element)
    {
        LogSefazCteEvent::updateOrCreate(
            [
                'chave' => $element->value('eventoCTe.infEvento.chCTe')->sole(),
                'tp_evento' => $element->value('eventoCTe.infEvento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('eventoCTe.infEvento.nSeqEvento')->sole(),
                'organization_id' => $organization->id,
            ],
            [
                'chave' => $element->value('eventoCTe.infEvento.chCTe')->sole(),
                'tp_evento' => $element->value('eventoCTe.infEvento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('eventoCTe.infEvento.nSeqEvento')->sole(),
                'dh_evento' => explode('T', $element->value('eventoCTe.infEvento.dhEvento')->sole())[0] . ' ' . explode('-', explode('T', $element->value('eventoCTe.infEvento.dhEvento')->sole())[1])[0],
                'xml' => $xml,
                'organization_id' => $organization->id,
            ]
        );
    }

    public function registerLogProcNfeEvent($organization, $xml, $element)
    {
        $carta_correcao = [];
        $log = LogSefazNfeEvent::updateOrCreate(
            [
                'chave' => $element->value('procEventoNFe.evento.chNFe')->sole(),
                'tp_evento' => $element->value('procEventoNFe.evento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('procEventoNFe.evento.nSeqEvento')->sole(),
                'organization_id' => $organization->id,
            ],
            [
                'chave' => $element->value('procEventoNFe.evento.chNFe')->sole(),
                'tp_evento' => $element->value('procEventoNFe.evento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('procEventoNFe.evento.nSeqEvento')->sole(),
                'dh_evento' => explode('T', $element->value('procEventoNFe.evento.dhEvento')->sole())[0] . ' ' . explode('-', explode('T', $element->value('procEventoNFe.evento.dhEvento')->sole())[1])[0],
                'x_evento' => $element->value('procEventoNFe.evento.detEvento.descEvento')->sole(),
                'xml' => $xml,
                'organization_id' => $organization->id,
            ]
        );

        $nfe = NotaFiscalEletronica::where('chave', $element->value('procEventoNFe.evento.chNFe')->sole())->first();

        if ($log->tp_evento == 110110 && $nfe) {


            if (isset($nfe->carta_correcao) && !empty($nfe->carta_correcao)) {

                $carta_correcao = $nfe->carta_correcao;
            }

            if (!in_array($log->id, $carta_correcao)) {
                $carta_correcao[] = $log->id;
            }

            $nfe->update(['carta_correcao' => $carta_correcao]);
        }
    }

    public function registerLogNfeEvent($organization, $xml, $element)
    {
        LogSefazNfeEvent::updateOrCreate(
            [
                'chave' => $element->value('resEvento.chNFe')->sole(),
                'tp_evento' => $element->value('resEvento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('resEvento.nSeqEvento')->sole(),
                'organization_id' => $organization->id,
            ],
            [
                'chave' => $element->value('resEvento.chNFe')->sole(),
                'tp_evento' => $element->value('resEvento.tpEvento')->sole(),
                'n_seq_evento' => $element->value('resEvento.nSeqEvento')->sole(),
                'dh_evento' => explode('T', $element->value('resEvento.dhRecbto')->sole())[0] . ' ' . explode('-', explode('T', $element->value('resEvento.dhRecbto')->sole())[1])[0],
                'x_evento' => $element->value('resEvento.xEvento')->sole(),
                'xml' => $xml,
               'organization_id' => $organization->id,
            ]
        );
    }
}
