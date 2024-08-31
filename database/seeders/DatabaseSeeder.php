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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@email.com',
            'password' => bcrypt('asdfasdf'),
        ]);

        $tenant1 = Tenant::create([
            'name' => 'Foo',
            'email' => 'email@email.com',
            'password' => bcrypt('Mudar@1234*'),
            'razao_social' => 'Foo Ltd.',
            'cnpj' => '11111111111111',
        ]);
        $tenant1->domains()->create(['domain' => 'foo.localhost']);

    }
}
