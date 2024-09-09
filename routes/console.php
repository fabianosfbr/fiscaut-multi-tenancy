<?php

use App\Models\Tenant;
use App\Models\PricePlan;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Str;
use App\Models\Tenant\PaymentLog;

use Illuminate\Support\Facades\Artisan;
use App\Enums\Tenant\PermissionTypeEnum;

Artisan::command('play', function () {

    $tenant = Tenant::first();

    $package = PricePlan::first();


    $subscription = [
        'package_id' => $package->id,
        'package_name' => $package->title,
        'package_price' => $package->price,
        'status' => 'pending',
        'name' => $tenant->name,
        'email' => $tenant->email,
        'tenant_id' => $tenant->id,
        'track' => Str::random(10) . Str::random(10),

    ];

  //  PaymentLog::create($subscription);


    $tenant->run(function () use ($subscription) {

        $user = User::where('email', tenant()->email)->first();

        $subscription['user_id'] = $user->id;
        $subscription['email'] = $user->email;
        $subscription['name'] = $user->name;

        PaymentLog::create($subscription);
        $payment_log = PaymentLog::where('tenant_id', tenant()->id)->first();

        dd($payment_log);
    });
});
