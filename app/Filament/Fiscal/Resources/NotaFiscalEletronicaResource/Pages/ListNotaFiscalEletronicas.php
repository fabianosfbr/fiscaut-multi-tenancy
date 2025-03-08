<?php

namespace App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Pages;

use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaFiscalEletronicas extends ListRecords
{
    protected static string $resource = NotaFiscalEletronicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
