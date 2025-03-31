<?php

namespace App\Filament\Fiscal\Resources\CteSaidaResource\Pages;

use App\Filament\Fiscal\Resources\CteSaidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCteSaida extends EditRecord
{
    protected static string $resource = CteSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
