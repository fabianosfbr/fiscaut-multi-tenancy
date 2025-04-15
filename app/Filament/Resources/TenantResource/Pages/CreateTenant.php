<?php

namespace App\Filament\Resources\TenantResource\Pages;

use Illuminate\Support\Str;
use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $this->data['cnpj'] = str_replace(['-', '.', '/'], '', $data['cnpj']);
        $this->data['password'] = bcrypt($this->data['password']);

        return $this->data;
    }

    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();

        $domain = Str::slug($this->data['domain']);
        $appDomain = config('app.domain');

        // Cria o domínio completo
        $fullDomain = $domain . '.' . $appDomain;

        $tenant->domains()->create([
            'domain' => $fullDomain,
        ]);

    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
