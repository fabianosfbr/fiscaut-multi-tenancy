<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\Tenant\Client;
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
        $this->data['domain'] = $data['domains'] . '.' . config('app.domain');
        $this->data['password'] = bcrypt($this->data['password']);
        unset($this->data['domains']);

        return $this->data;
    }


    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();
        $tenant->domains()->create([
            'domain' => $this->data['domain'],
        ]);
    }
    //     // $tenant = $this->getRecord();

    //     // DB::beginTransaction();

    //     // $tenant->domains()->create([
    //     //     'domain' => $this->data['domain'],
    //     // ]);

    //     // $tenant->run(function ($tenant) {
    //     //     $user = Client::create($tenant->only('name', 'email', 'password'));

    //     //     // $organization = Organization::create([
    //     //     //     'razao_social' => $tenant->razao_social,
    //     //     //     'cnpj' => $tenant->cnpj,
    //     //     // ]);

    //     //     // $user->organizations()->attach($organization->id);

    //     //     if ($user) {
    //     //         DB::commit();
    //     //     } else {
    //     //         DB::rollBack();
    //     //     }
    //     // });
    // }
}
