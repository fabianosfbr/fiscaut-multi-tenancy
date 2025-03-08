<?php

namespace App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Pages;

use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotaFiscalEletronica extends EditRecord
{
    protected static string $resource = NotaFiscalEletronicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
