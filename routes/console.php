<?php

use App\Models\Tenant;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use App\Models\Tenant\Permission;
use App\Enums\Tenant\UserTypeEnum;
use App\Models\Tenant\Organization;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Enums\Tenant\PermissionTypeEnum;

Artisan::command('play', function () {

    $tenant = Tenant::first();
    $tenant->run(function () {

        // $organization = Organization::find('9ce28135-70ef-4f47-9acb-56aa624d475f');
        // $user = User::find('9ce28135-7011-454c-aad0-945852cc4a3c');


        $roles = UserTypeEnum::toArray();
        $permissions = PermissionTypeEnum::toArray();

        foreach ($permissions as $name => $description) {
            dd($name, $description);
            // $permission = Permission::create([
            //     'name' => $valuePermission,
            //     'organization_id' => $organization->id,
            // ]);

            $permissionsCollection[] = $permission;
        }




        $roles = $organization->roles;


        //dd($roles->pluck('name')->toArray());
        //  $user->syncRoles($roles->pluck('name')->toArray());
        dd($user->roles);

        // foreach ($roles as $role) {

        //     $permissions = $role->permissions;

        //     foreach ($permissions as $permission) {
        //         $user->givePermissionTo($permission->name);
        //     }

        // };


    });
});
