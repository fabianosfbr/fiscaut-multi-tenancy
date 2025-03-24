<?php

namespace App\Filament\Fiscal\Resources\NfeSaidaResource\Pages;

use App\Filament\Fiscal\Resources\NfeSaidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNfeSaida extends EditRecord
{
    protected static string $resource = NfeSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
