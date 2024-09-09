<?php

namespace App\Filament\Resources\PricePlanResource\Pages;

use App\Filament\Resources\PricePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPricePlans extends ListRecords
{
    protected static string $resource = PricePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
