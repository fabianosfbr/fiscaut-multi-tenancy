<?php

namespace App\Filament\Contabil\Resources\FornecedorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Konnco\FilamentImport\Actions\ImportField;
use Konnco\FilamentImport\Actions\ImportAction;
use App\Filament\Contabil\Resources\FornecedorResource;
use App\Models\Fornecedor;

class ListFornecedors extends ListRecords
{
    protected static string $resource = FornecedorResource::class;

    protected static ?string $title = '';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
