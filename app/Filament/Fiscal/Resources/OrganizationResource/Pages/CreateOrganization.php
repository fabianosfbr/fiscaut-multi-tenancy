<?php

namespace App\Filament\Fiscal\Resources\OrganizationResource\Pages;

use App\Filament\Fiscal\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public static ?string $title = 'Adicionar Empresa';

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['validade_certificado'] = now()->parse($data['validade_certificado']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
