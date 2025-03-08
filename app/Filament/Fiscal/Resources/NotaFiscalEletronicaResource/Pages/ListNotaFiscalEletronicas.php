<?php

namespace App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Pages;

use Filament\Actions;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;
use Filament\Resources\Pages\ListRecords;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource;

class ListNotaFiscalEletronicas extends ListRecords
{
    protected static string $resource = NotaFiscalEletronicaResource::class;

    #[On('apply-tag-nfe')]
    public function aplicarEtiqueta(string $tag, string $nfe)
    {
        $nfe = NotaFiscalEletronica::find($nfe);
        $nfe->retag($tag);

        Cache::forget('tagging_summary_' . $nfe->cnpj_emitente);
        
        $this->dispatch('refresh-table');
    }
}
