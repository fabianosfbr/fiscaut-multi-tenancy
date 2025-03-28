<?php

namespace App\Filament\Fiscal\Resources\AcumuladorResource\Pages;

use App\Filament\Fiscal\Resources\AcumuladorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcumulador extends CreateRecord
{
    protected static string $resource = AcumuladorResource::class;


    protected static bool $canCreateAnother = false;



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = getOrganizationCached()->id;

        return $data;
    }
}
