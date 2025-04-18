<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Client\Pages\Auth\LoginPage;
use App\Providers\Filament\Traits\SharedPanelConfiguration;

class GedPanelProvider extends PanelProvider
{
    use SharedPanelConfiguration;

    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('ged')
            ->path('ged');

        $panel
            ->discoverResources(in: app_path('Filament/Ged/Resources'), for: 'App\\Filament\\Ged\\Resources')
            ->discoverPages(in: app_path('Filament/Ged/Pages'), for: 'App\\Filament\\Ged\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Ged/Widgets'), for: 'App\\Filament\\Ged\\Widgets')
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
