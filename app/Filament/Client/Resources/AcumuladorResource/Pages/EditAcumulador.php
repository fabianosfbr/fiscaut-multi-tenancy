<?php

namespace App\Filament\Client\Resources\AcumuladorResource\Pages;

use App\Filament\Client\Resources\AcumuladorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcumulador extends EditRecord
{
    protected static string $resource = AcumuladorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
