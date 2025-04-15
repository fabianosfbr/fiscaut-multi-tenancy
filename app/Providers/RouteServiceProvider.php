<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Registrar rotas dos painéis
            Route::middleware('web')
                ->group(base_path('routes/fiscal.php'));

            Route::middleware('web')
                ->group(base_path('routes/contabil.php'));

            Route::middleware('web')
                ->group(base_path('routes/gerencial.php'));
        });
    }
} 