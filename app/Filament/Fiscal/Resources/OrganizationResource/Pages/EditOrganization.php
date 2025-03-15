<?php

namespace App\Filament\Fiscal\Resources\OrganizationResource\Pages;

use DateTime;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Fiscal\Resources\OrganizationResource;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    public static ?string $title = 'Editar Organização';


    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['senha_certificado']);
        unset($data['path_certificado']);
        $data['validade_certificado'] = now()->parse($data['validade_certificado'])->format('d/m/Y');
        return $data;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['validade_certificado']) {
            $data['validade_certificado'] = DateTime::createFromFormat('d/m/Y', $data['validade_certificado']);
        }
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
