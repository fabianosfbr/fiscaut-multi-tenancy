<?php

namespace App\Listeners;

use App\Models\Tenant\UserPanelPermission;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\RegisterPermissionForUserOrganizationEvent;

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
    public function handle(RegisterPermissionForUserOrganizationEvent $event): void
    {
        $user = $event->user;
        $roles = $event->roles;

        $user->syncRolesWithOrganization(array_keys($roles), $user->last_organization_id);


    }
}