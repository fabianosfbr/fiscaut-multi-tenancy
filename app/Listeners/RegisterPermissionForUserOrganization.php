<?php

namespace App\Listeners;

use App\Events\CreateOrganizationProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;

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
