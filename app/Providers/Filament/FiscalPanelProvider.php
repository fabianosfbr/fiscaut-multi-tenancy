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
use App\Filament\Fiscal\Widgets\DocsOverview;
use App\Filament\Fiscal\Pages\Importar\NfeCte;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Fiscal\Widgets\TopProdutosChart;
use App\Http\Middleware\CheckUserHasOrganization;
use App\Filament\Fiscal\Widgets\TopProdutosWidget;
use App\Filament\Clusters\Profile\Pages\ViewProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


class FiscalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('fiscal')
            ->path('fiscal')
            ->breadcrumbs(false)
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->viteTheme('resources/css/filament/fiscal/theme.css')
            ->discoverResources(in: app_path('Filament/Fiscal/Resources'), for: 'App\\Filament\\Fiscal\\Resources')
            ->discoverPages(in: app_path('Filament/Fiscal/Pages'), for: 'App\\Filament\\Fiscal\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
               // TopProdutosChart::class,
            ])
            ->navigationGroups(config('sidebar'))
            ->discoverWidgets(in: app_path('Filament/Fiscal/Widgets'), for: 'App\\Filament\\Fiscal\\Widgets')
            ->widgets([
                DocsOverview::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Meu Perfil')
                    ->icon('heroicon-o-user')
                    ->url(fn(): string => ViewProfile::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn(): string => Blade::render('@livewire(\'component.choice-organization\')'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->middleware([
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                CheckUserHasOrganization::class,
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \Hasnayeen\Themes\ThemesPlugin::make(),
                // HooksHelperPlugin::make(),
            ])
            ->databaseNotifications();
    }
}
