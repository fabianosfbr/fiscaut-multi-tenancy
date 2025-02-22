<?php

namespace Database\Seeders;

use App\Enums\Tenant\PaymentLogStatusEnum;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@email.com',
            'password' => bcrypt('asdfasdf'),
        ]);

        $tenant = Tenant::create([
            'name' => 'Foo',
            'email' => 'email@email.com',
            'password' => bcrypt('asdfasdf'),
            'razao_social' => 'Foo Ltd.',
            'cnpj' => '11111111111111',
        ]);
        $tenant->domains()->create(['domain' => 'foo.localhost']);

        $package = PricePlan::where('title', 'Premium Plan')->first();

        $subscription = [
            'package_id' => $package->id,
            'package_name' => $package->title,
            'package_price' => $package->price,
            'status' => PaymentLogStatusEnum::PAID->value,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'tenant_id' => $tenant->id,
            'start_date' => now(),
            'expire_date' => '2100-12-31',
            'track' => Str::random(10).Str::random(10),

        ];

        PaymentLog::create($subscription);

        $tenant->run(function () use ($subscription) {

            // $user = User::where('email', tenant()->email)->first();

            $subscription['user_id'] = tenant()->id;
            $subscription['email'] = tenant()->email;
            $subscription['name'] = tenant()->name;

            PaymentLog::create($subscription);
        });
    }
}
