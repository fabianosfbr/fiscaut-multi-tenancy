<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant\Client;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tenant;
use App\Models\Tenant\Organization;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PricePlanSeeder::class,
            TenantSeeder::class,
        ]);



    }
}
