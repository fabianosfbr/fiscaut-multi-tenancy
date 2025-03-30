<?php

namespace App\Filament\Fiscal\Resources\CteEntradaResource\Pages;

use App\Filament\Fiscal\Resources\CteEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCteEntrada extends EditRecord
{
    protected static string $resource = CteEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
