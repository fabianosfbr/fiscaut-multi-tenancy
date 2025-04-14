<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use App\Filament\Fiscal\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Fiscal\Pages\ClientesReport;
use App\Filament\Fiscal\Widgets\DocsOverview;
use App\Filament\Fiscal\Pages\Importar\NfeCte;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Fiscal\Pages\FornecedoresReport;
use App\Filament\Fiscal\Widgets\TopProdutosChart;
use App\Http\Middleware\CheckUserHasOrganization;
use App\Filament\Fiscal\Widgets\TopProdutosWidget;
use App\Filament\Clusters\Profile\Pages\ViewProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Filament\Fiscal\Pages\FaturamentoMensalReport;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Providers\Filament\Traits\SharedPanelConfiguration;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


class FiscalPanelProvider extends PanelProvider
{
    use SharedPanelConfiguration;

    public function panel(Panel $panel): Panel
    {
         $panel
            ->id('fiscal')
            ->path('fiscal');

        $panel
            ->viteTheme('resources/css/filament/fiscal/theme.css')
            ->discoverResources(in: app_path('Filament/Fiscal/Resources'), for: 'App\\Filament\\Fiscal\\Resources')
            ->discoverPages(in: app_path('Filament/Fiscal/Pages'), for: 'App\\Filament\\Fiscal\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
                ClientesReport::class,
                FornecedoresReport::class,
                FaturamentoMensalReport::class,
            ])
            ->navigationGroups(config('sidebar'))
            ->discoverWidgets(in: app_path('Filament/Fiscal/Widgets'), for: 'App\\Filament\\Fiscal\\Widgets')
            ->widgets([
                DocsOverview::class,
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
