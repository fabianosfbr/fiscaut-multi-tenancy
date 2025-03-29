<?php

namespace App\Filament\Fiscal\Resources\OrganizationResource\Pages;

use Filament\Actions;
use App\Models\Tenant\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Fiscal\Resources\OrganizationResource;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public static ?string $title = 'Adicionar Empresa';

    public function mutateFormDataBeforeCreate(array $data): array
    {        
        
        return $data;
    }

    public function afterCreate(): void
    {
        $user = Auth::user();
        $user->organizations()->attach($this->record->id, ['is_active' => true]);
        // add roles to user
        $roles = Role::all()->pluck('name', 'id')->toArray();

        $user->syncRolesWithOrganization($roles, $this->record->id);
        
        Cache::forget('all_valid_organizations_for_user_'.$user->id);
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
