<?php

use App\Models\Tenant;
use App\Models\PricePlan;
use App\Models\Tenant\Tag;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Tenant\PaymentLog;
use App\Models\Tenant\Permission;
use App\Enums\Tenant\UserTypeEnum;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Enums\Tenant\PermissionTypeEnum;
use Illuminate\Support\Facades\Schedule;
use App\Services\Tenant\Sefaz\NfeService;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Http\Client\RequestException;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use App\Services\Fiscal\SiegConnectionService;
use App\Services\Fiscal\SefazConnectionService;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;
use App\Models\Tenant\ShowChoiceOrganizationUrl;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;


//Schedule::command('analytics:update')->dailyAt('02:00');
//Schedule::command('scheduled-commands:run')->everyMinute();


Artisan::command('play', function () {


    $content = 'Who am I?'; // Your desired prompt or content
    $model = 'mistralai/mistral-7b-instruct:free';
    $messageData = new MessageData(
        content: $content,
        role: RoleType::USER,
    );

    $chatData = new ChatData(
        messages: [
            $messageData,
        ],
        model: $model,
        max_tokens: 100, // Adjust this value as needed
    );

    $response = LaravelOpenRouter::chatRequest($chatData);

    $content = Arr::get($response->choices[0], 'message.content');
    dd($content);


    // $apiUrl = 'https://api.sieg.com/BaixarXmls';
    // $apiKey = 'tPSP92W2wug4gDwurCvF3Q==';


    // try {
    //     $requestData = [
    //         'XmlType' => 1,
    //         'DataEmissaoInicio' => '2025-03-17T00:00:00.000Z',
    //         'DataEmissaoFim' => '2025-03-17T23:59:59.999Z',
    //         'CnpjEmit' => '08357463000117',
    //         'skip' => 60,
    //         'take' => 50,
    //         'Downloadevent' => false,
    //     ];

    //     $response = Http::retry(3, 100)
    //     ->post(
    //         $apiUrl . '?api_key=' . $apiKey,
    //         $requestData
    //     )
    //     ->throw();

    //     dd($response->status());
    //     //code...
    // } catch (RequestException $e) {
    //     if ($e->response->status() === 404) {
    //         $errorMessage = $e->response->json()['message'] ?? $e->response->body();

    //         if (str_contains($errorMessage, 'Nenhum arquivo XML localizado')) {
    //             // Trata como sucesso com mensagem específica
    //             dd('sucesso');
    //         }
    //     }
    // }


    // dd('parei');


    $tenant = Tenant::where('id', '4f4a0af5-30c7-4122-9d5f-094ad5d53a78')->first();

    $tenant->run(function ($tenant) {
        $organization = Organization::where('cnpj', '08357463000117')->first();


        $service = new SefazConnectionService($organization);

        //$result = $service->verificarNsusFaltantes();
        $result = $service->consultarNFeDestinadas(439789);
        //$result = $service->consultarCTeDestinados(246716);

        dd($result);
    });



    // $tenant->run(function ($tenant) {

    //     DB::beginTransaction();

    //     $user = User::create([
    //         'name' => $tenant->name,
    //         'email' => $tenant->email,
    //         'password' => $tenant->password,
    //     ]);

    //     $organization = Organization::create([
    //         'razao_social' => $tenant->razao_social.':'.$tenant->cnpj,
    //         'cnpj' => $tenant->cnpj,
    //     ]);

    //     $user->organizations()->attach($organization->id);

    //     $user->last_organization_id = $organization->id;
    //     $user->saveQuietly();

    //     $roles = UserTypeEnum::toArray();
    //     $permissions = PermissionTypeEnum::toArray();

    //     $permissionsCollection = [];

    //     foreach ($permissions as $name => $description) {

    //         $permission = Permission::create([
    //             'name' => $name,
    //             'description' => $description,
    //         ]);

    //         $permissionsCollection[] = $permission;
    //     }

    //     foreach ($roles as $name => $description) {
    //         $role = Role::create([
    //             'name' => $name,
    //             'description' => $description,
    //         ]);

    //         foreach ($permissionsCollection as $key => $permission) {
    //             $role->givePermissionTo($permission);
    //         }

    //     }

    //     $user->syncRoles(array_keys($roles));

    //     DB::commit();

    //     dd('funfou');

    //     $nfe = NotaFiscalEletronica::where('chave', '35230300565813000129550030009184021879812438')->first();

    //     $tag = Tag::find('9cfdd5ad-1e8e-409b-842f-b641d8d5b199');

    //     //  $nfe->tag($tag, $nfe->vNfe);

    //     // dd($nfe->tagging_summary);

    //     dd($nfe->tagging_summary);

    //     dd('tagged');

    //     // $user = User::where('email', tenant()->email)->first();

    //     // $organization =  Organization::findOrFail($user->last_organization_id);

    //     // $service = app(NfeService::class);

    //     // $service->issuer($organization);

    //     // $service->buscarDocumentosFiscaisPorNsu(82603);

    //     dd('parei');

    //     dd($organization->digitalCertificate->content_file);

    //     $subscription['user_id'] = $user->id;
    //     $subscription['email'] = $user->email;
    //     $subscription['name'] = $user->name;

    //     PaymentLog::create($subscription);
    //     $payment_log = PaymentLog::where('tenant_id', tenant()->id)->first();

    //     dd($payment_log);
    // });


    $tenant->run(function ($tenant) {
        $organization = Organization::where('cnpj', '08357463000117')->first();

        $nfes = NotaFiscalEletronica::entradasProprias($organization)->get();

        dd($nfes);

        foreach ($nfes as $nfe) {
            $content = $nfe->xml_content;

            $xmlReader = new XmlNfeReaderService();
            $xmlReader->loadXml($content)
                ->parse()
                ->setOrigem('SEFAZ')
                ->save();
        }

        dd('parei');



        $service = new SiegConnectionService($organization);

        // $resultado = $service->baixarXmlsPorPeriodo(
        //     '2025-03-17',
        //     '2025-03-17',
        //     SiegConnectionService::XML_TYPE_NFE,
        //     false // com eventos
        // );

        $resultado = $service->baixarTodosXmlsNFePorPeriodo(
            '2025-03-17',
            '2025-03-17',
            SiegConnectionService::XML_TYPE_NFE,
            'emitente',
            false // com eventos
        );

        dd($resultado);



        $result = $service->consultarDocumentosPorPeriodo('2023-01-01', '2023-01-31');

        dd($result);
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
