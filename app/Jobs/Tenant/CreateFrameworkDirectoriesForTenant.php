<?php

namespace App\Jobs\Tenant;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Stancl\Tenancy\Contracts\Tenant;

class CreateFrameworkDirectoriesForTenant implements ShouldQueue
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
        $this->tenant->run(function ($tenant) {
            $storage_path = storage_path();

            mkdir("$storage_path/framework/cache", 0777, true);
        });

    }
}
