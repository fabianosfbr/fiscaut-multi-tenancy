<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Client\Pages\Auth\LoginPage;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Client\Pages\Auth\RegisterPage;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Client\Pages\Auth\PasswordReset;
use App\Http\Middleware\CheckUserHasOrganization;
use Agencetwogether\HooksHelper\HooksHelperPlugin;
use App\Filament\Clusters\Profile\Pages\ViewProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Providers\Filament\Traits\SharedPanelConfiguration;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;

class ClientPanelProvider extends PanelProvider
{
    use SharedPanelConfiguration;
    
    public function panel(Panel $panel): Panel
    {
        
        $panel
            ->id('client')
            ->path('app');

            $panel
             ->viteTheme('resources/css/filament/client/theme.css')
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ]);

            $panel = $this->getSharedBaseConfiguration($panel);

            $panel = $this->getUserMenuConfiguration($panel);
    
            $panel = $this->getOrganizationSelectorConfiguration($panel);
    
            $panel = $this->getSharedMiddlewareConfiguration($panel);
    
            $panel = $this->getTenantMiddlewareConfiguration($panel);
    
            $panel = $this->getSharedPluginsConfiguration($panel);
    
    
            return $panel;
    }
}
