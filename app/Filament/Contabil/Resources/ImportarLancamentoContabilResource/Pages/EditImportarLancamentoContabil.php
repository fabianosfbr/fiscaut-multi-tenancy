<?php

namespace App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\Pages;

use App\Filament\Contabil\Resources\ImportarLancamentoContabilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImportarLancamentoContabil extends EditRecord
{
    protected static string $resource = ImportarLancamentoContabilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
