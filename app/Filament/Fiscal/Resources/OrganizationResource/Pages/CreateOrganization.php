<?php

namespace App\Filament\Fiscal\Resources\OrganizationResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Fiscal\Resources\OrganizationResource;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public static ?string $title = 'Adicionar Empresa';

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['validade_certificado'] = now()->parse($data['validade_certificado']);
        return $data;
    }

    public function afterCreate(): void
    {
        $user = Auth::user();
        $user->organizations()->attach($this->record->id, ['is_active' => true]);
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
