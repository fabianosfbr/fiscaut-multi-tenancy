<?php

namespace App\Filament\Fiscal\Resources\OrganizationResource\Pages;

use App\Filament\Fiscal\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Adicionar Empresa'),
        ];
    }
}
