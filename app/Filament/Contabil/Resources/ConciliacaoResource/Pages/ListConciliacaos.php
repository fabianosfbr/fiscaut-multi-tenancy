<?php

namespace App\Filament\Contabil\Resources\ConciliacaoResource\Pages;

use App\Filament\Contabil\Resources\ConciliacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConciliacaos extends ListRecords
{
    protected static string $resource = ConciliacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
