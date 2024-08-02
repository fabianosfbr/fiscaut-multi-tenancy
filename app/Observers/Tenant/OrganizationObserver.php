<?php

namespace App\Observers\Tenant;

use App\Models\Tenant\Role;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Organization;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        $roles = config('roles-permissions.roles');
        $permissions = config('roles-permissions.permissions');

        foreach ($permissions as $key => $valuePermission) {
            $permission = Permission::create([
                'name' => $valuePermission,
                'organization_id' => $organization->id,
            ]);

            $permissionsCollection[] = $permission;
        }

        foreach ($roles as $key => $valueRole) {
            $role = Role::create([
                'name' => $valueRole,
                'organization_id' => $organization->id,
            ]);

            foreach ($permissionsCollection as $key => $permission) {
                $role->permissions()->attach($permission);
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
