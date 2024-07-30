<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tenant;
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
            'email' => 'foo@email.com',
            'password' => bcrypt('asdfasdf'),
            'razao_social' => 'Foo Ltd.',
            'cnpj' => '11111111111111',
        ]);
        $tenant1->domains()->create(['domain' => 'foo.localhost']);
    }
}
