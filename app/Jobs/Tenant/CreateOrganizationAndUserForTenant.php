<?php

namespace App\Jobs\Tenant;

use App\Enums\Tenant\PermissionTypeEnum;
use App\Enums\Tenant\UserTypeEnum;
use App\Events\RegisterPermissionForUserOrganizationEvent;
use App\Events\RegisterPanelForUserOrganizationEvent;
use App\Models\Tenant;
use App\Models\Tenant\Organization;
use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CreateOrganizationAndUserForTenant implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Tenant $tenant
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->tenant->run(function () {
            try {
                DB::beginTransaction();

                $user = User::create([
                    'name' => $this->tenant->name,
                    'email' => $this->tenant->email,
                    'password' => $this->tenant->password,
                    'owner' => true,
                ]);

                $organization = Organization::create([
                    'razao_social' => $this->tenant->razao_social.':'.$this->tenant->cnpj,
                    'cnpj' => $this->tenant->cnpj,
                ]);

                $user->organizations()->attach($organization->id);

                $user->last_organization_id = $organization->id;
                $user->saveQuietly();

                $roles = $this->registerRolesAndPermissionsForUser();

                event(new RegisterPermissionForUserOrganizationEvent($user, $roles));
                event(new RegisterPanelForUserOrganizationEvent($user, config('admin.panels')));
               
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
            }
        });
    }

    private function registerRolesAndPermissionsForUser(): array
    {
        $roles = UserTypeEnum::toArray();
        $permissions = PermissionTypeEnum::toArray();

        $permissionsCollection = [];

        foreach ($permissions as $name => $description) {

            $permission = Permission::create([
                'name' => $name,
                'description' => $description,
                'guard_name' => 'web',
            ]);

            $permissionsCollection[] = $permission;
        }

        foreach ($roles as $name => $description) {
            $role = Role::create([
                'name' => $name,
                'description' => $description,
                'guard_name' => 'web',
            ]);

            foreach ($permissionsCollection as $key => $permission) {
                $role->givePermissionTo($permission);
            }
        }

        return $roles;
    }
}
