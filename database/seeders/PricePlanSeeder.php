<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant\PricePlan;
use Illuminate\Support\Facades\DB;
use App\Enums\Tenant\PricePlanTypEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PricePlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('price_plans')->insert([
            [
                'title' => 'Simple Plan',
                'type' => PricePlanTypEnum::MONTHLY,
                'status' => 1,
                'price' => 200.00,
                'has_trial' => 1,
                'trial_days' => 10,
                'documents_permission_feature' => 10000,
                'users_permission_feature' => 5,
                'storage_permission_feature' => 100,
                'package_badge' => 'Basic',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Medium Plan',
                'type' => PricePlanTypEnum::MONTHLY,
                'status' => 1,
                'price' => 300.00,
                'has_trial' => 1,
                'trial_days' => 10,
                'documents_permission_feature' => 15000,
                'users_permission_feature' => 8,
                'storage_permission_feature' => 200,
                'package_badge' => 'Royal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Premium Plan',
                'type' => PricePlanTypEnum::MONTHLY,
                'status' => 1,
                'price' => 400.00,
                'has_trial' => 1,
                'documents_permission_feature' => 20000,
                'users_permission_feature' => 10,
                'storage_permission_feature' => 300,
                'trial_days' => 10,
                'package_badge' => 'Enterprise',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
