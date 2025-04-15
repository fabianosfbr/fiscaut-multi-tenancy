<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

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
        try {
            $tenant = $this->getRecord();
            
            // Formata o domínio para garantir que seja válido
            $domain = Str::slug($this->data['domain']);
            $appDomain = config('app.domain');
            
            if (empty($appDomain)) {
                throw new \Exception('APP_DOMAIN não está configurado no arquivo .env');
            }

            // Cria o domínio completo
            $fullDomain = $domain . '.' . $appDomain;

            // Valida o domínio
            if (!filter_var('http://' . $fullDomain, FILTER_VALIDATE_URL)) {
                throw new \Exception('O domínio gerado é inválido: ' . $fullDomain);
            }

            // Cria o domínio para o tenant
            $tenant->domains()->create([
                'domain' => $fullDomain,
            ]);

            Notification::make()
                ->title('Tenant criado com sucesso')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao criar domínio')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
