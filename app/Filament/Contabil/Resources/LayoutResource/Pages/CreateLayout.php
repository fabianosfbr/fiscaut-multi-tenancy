<?php

namespace App\Filament\Contabil\Resources\LayoutResource\Pages;

use App\Filament\Contabil\Resources\LayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLayout extends CreateRecord
{
    protected static string $resource = LayoutResource::class;


    protected static ?string $title = 'Criar Leiaute';


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = getOrganizationCached()->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
