<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $this->data['cnpj'] = sanitize($data['cnpj']);
        $this->data['password'] = bcrypt($this->data['password']);

        return $this->data;
    }

    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();
        $tenant->domains()->create([
            'domain' => $this->data['domain'].'.'.config('app.domain'),
        ]);

    }
}
