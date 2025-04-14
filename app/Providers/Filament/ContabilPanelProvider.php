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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Http\Middleware\CheckUserHasOrganization;
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

class ContabilPanelProvider extends PanelProvider
{
    use SharedPanelConfiguration;
    
    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('contabil')
            ->path('contabil');

        $panel
            ->viteTheme('resources/css/filament/contabil/theme.css')
            ->discoverResources(in: app_path('Filament/Contabil/Resources'), for: 'App\\Filament\\Contabil\\Resources')
            ->discoverPages(in: app_path('Filament/Contabil/Pages'), for: 'App\\Filament\\Contabil\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
              //  Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Contabil/Widgets'), for: 'App\\Filament\\Contabil\\Widgets')
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
