<?php

namespace App\Filament\Fiscal\Resources\AcumuladorResource\Pages;

use App\Filament\Fiscal\Resources\AcumuladorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcumuladors extends ListRecords
{
    protected static string $resource = AcumuladorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
