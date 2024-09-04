<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Role;
use App\Models\Tenant\Client;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\CreateOrganizationProcessed;

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
                ]);

                $organization = Organization::create([
                    'razao_social' => $this->tenant->razao_social . ':' . $this->tenant->cnpj,
                    'cnpj' => $this->tenant->cnpj,
                ]);

                $user->organizations()->attach($organization->id);

                $user->last_organization_id = $organization->id;
                $user->saveQuietly();

                event(new CreateOrganizationProcessed($user, $organization));

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
            }
        });
    }

}
