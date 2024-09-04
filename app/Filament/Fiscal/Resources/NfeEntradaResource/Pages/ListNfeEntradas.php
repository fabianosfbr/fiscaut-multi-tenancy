<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;

use App\Filament\Fiscal\Resources\NfeEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNfeEntradas extends ListRecords
{
    protected static string $resource = NfeEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
