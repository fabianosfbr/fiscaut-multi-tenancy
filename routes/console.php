<?php

use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Models\Tenant\Organization;
use App\Models\Tenant\PaymentLog;
use App\Models\Tenant\User;
use App\Services\Tenant\Sefaz\NfeService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('play', function () {

    $tenant = Tenant::first();

    $tenant->run(function ()  {


        $nfe = NotaFiscalEletronica::whereJsonContains('aut_xml', ['CNPJ' => '15705134000185'])->first();


        $user = User::where('email', tenant()->email)->first();

        $organization =  Organization::findOrFail($user->last_organization_id);

        $service = app(NfeService::class);

        $service->issuer($organization);


        $service->buscarDocumentosFiscaisPorNsu(82603);

        dd('parei');

        dd($organization->digitalCertificate->content_file);

        $subscription['user_id'] = $user->id;
        $subscription['email'] = $user->email;
        $subscription['name'] = $user->name;

        PaymentLog::create($subscription);
        $payment_log = PaymentLog::where('tenant_id', tenant()->id)->first();

        dd($payment_log);
    });


    dd('pare');

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
