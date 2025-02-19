<?php

namespace App\Filament\Resources\TenantResource\Pages;


use Filament\Actions;
use App\Models\Tenant\User as Client;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

use function Symfony\Component\String\b;

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
        $tenant->domains()->create([
            'domain' => $this->data['domain'] . '.' . config('app.domain'),
        ]);

    }
}
