<?php

namespace App\Listeners;

use App\Models\Tenant\UserPanelPermission;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\RegisterPanelForUserOrganizationEvent;

class RegisterPanelForUserOrganization implements ShouldQueue
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
    public function handle(RegisterPanelForUserOrganizationEvent $event): void
    {
        $user = $event->user;
        $panels = $event->panels;

        UserPanelPermission::syncPermissions($user, $panels);
        

    }
}
