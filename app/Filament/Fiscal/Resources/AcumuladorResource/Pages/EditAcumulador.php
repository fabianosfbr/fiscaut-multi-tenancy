<?php

namespace App\Filament\Fiscal\Resources\AcumuladorResource\Pages;

use App\Filament\Fiscal\Resources\AcumuladorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcumulador extends EditRecord
{
    protected static string $resource = AcumuladorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
