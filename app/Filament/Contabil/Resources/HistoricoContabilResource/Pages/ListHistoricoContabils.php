<?php

namespace App\Filament\Contabil\Resources\HistoricoContabilResource\Pages;

use App\Filament\Contabil\Resources\HistoricoContabilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistoricoContabils extends ListRecords
{
    protected static string $resource = HistoricoContabilResource::class;

    protected static ?string $title = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
