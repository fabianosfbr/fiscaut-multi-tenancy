<?php

namespace App\Filament\Contabil\Resources\ClienteResource\Pages;

use Filament\Actions;
use App\Models\Tenant\Cliente;
use Filament\Resources\Pages\ListRecords;
use Konnco\FilamentImport\Actions\ImportField;
use Konnco\FilamentImport\Actions\ImportAction;
use App\Filament\Contabil\Resources\ClienteResource;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected static ?string $title = '';

    protected function getHeaderActions(): array
    {
        return [
           ];
    }
}
