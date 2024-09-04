<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;

use App\Filament\Fiscal\Resources\NfeEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNfeEntrada extends EditRecord
{
    protected static string $resource = NfeEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
