<?php

namespace App\Filament\Resources\TenantResource\Pages;

use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
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

    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->getModel())($data);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        $domain = Str::slug($this->data['domain']);
        $appDomain = config('app.domain');

        // Cria o domínio completo
        $fullDomain = $domain . '.' . $appDomain;

        $record->domains()->create([
            'domain' => $fullDomain,
        ]);

        return $record;
    }
   

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
