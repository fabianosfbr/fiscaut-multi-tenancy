<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;


    protected function afterCreate(): void
    {
        $tenant = $this->getRecord();

        DB::beginTransaction();

        $tenant->domains()->create([
            'domain' => $this->data['domain'],
        ]);

        $tenant->run(function ($tenant) {
            $user = User::create($tenant->only('name', 'email', 'password'));
            if ($user) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        });
    }
}
