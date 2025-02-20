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
        $roles = $event->roles;

        $user->syncRolesWithOrganization(array_keys($roles), $user->last_organization_id);


    }
}
