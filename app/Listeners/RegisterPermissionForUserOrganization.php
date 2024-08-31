<?php

namespace App\Listeners;

use App\Models\Tenant\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\CreateOrganizationProcessed;

class RegisterPermissionForUserOrganization implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CreateOrganizationProcessed $event): void
    {
        $user = $event->user;

        $organization = $event->organization;

        $roles = $organization->roles;

        $user->syncRoles($roles->pluck('name')->toArray());

        // foreach ($user->roles as $roles) {
        //     $roles->permissions->each(function ($permission) use ($user) {
        //         $user->givePermissionTo($permission->name);
        //     });
        // }
    }
}
