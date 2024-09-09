<?php

namespace App\Filament\Resources\PricePlanResource\Pages;

use App\Filament\Resources\PricePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricePlan extends EditRecord
{
    protected static string $resource = PricePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
