<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Role;
use App\Models\Tenant\Permission;
use App\Enums\Tenant\{UserTypeEnum, PermissionTypeEnum};
use App\Models\Tenant\Organization;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        $roles = UserTypeEnum::toArray();
        $permissions = PermissionTypeEnum::toArray();


        $permissionsCollection = [];

        foreach ($permissions as $name => $description) {

            $permission = Permission::create([
                'name' => $name,
                'description' => $description,
                'organization_id' => $organization->id,
            ]);

            $permissionsCollection[] = $permission;
        }

        foreach ($roles as $name => $description) {
            $role = Role::create([
                'name' => $name,
                'description' => $description,
                'organization_id' => $organization->id,
            ]);

            foreach ($permissionsCollection as $key => $permission) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $organization): void
    {
        //
    }
}
