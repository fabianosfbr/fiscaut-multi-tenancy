<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        function () {

            $centralDomain = config('tenancy.central_domains');

            foreach ($centralDomain as $domain) {
                Route::middleware('web')
                    ->domain($domain)
                    ->group(base_path('routes/web.php'));
            }

            Route::middleware('web')->group(base_path('routes/tenant.php'));
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('universal', []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException $exception, Request $request) {
            return redirect(env('CENTRAL_DOMAIN'));
        });
    })->create();
