<?php

namespace App\Filament\Fiscal\Resources\CteTomadaResource\Pages;

use App\Filament\Fiscal\Resources\CteTomadaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCteTomada extends EditRecord
{
    protected static string $resource = CteTomadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
