<?php

namespace App\Providers\Filament;

use App\Filament\Client\Pages\Auth\LoginPage;
use App\Filament\Client\Pages\Auth\PasswordReset;
use App\Filament\Client\Pages\Auth\RegisterPage;
use App\Filament\Clusters\Profile\Pages\ViewProfile;
use App\Http\Middleware\CheckUserHasOrganization;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('app')
            ->brandLogo(asset('images/application/logo-lading-white.png'))
            ->darkModeBrandLogo(asset('images/application/logo-lading-black.png'))
            ->brandLogoHeight('38px')
            ->colors([
                'primary' => Color::Blue,
            ])

            ->login(LoginPage::class)
            ->registration(RegisterPage::class)
            ->emailVerification(EmailVerificationPrompt::class)
            ->passwordReset(
                resetAction: PasswordReset::class
            )

            ->viteTheme('resources/css/filament/client/theme.css')
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Meu Perfil')
                    ->icon('heroicon-o-user')
                    ->url(fn (): string => ViewProfile::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn (): string => Blade::render('@livewire(\'component.choice-organization\')'),
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
            ->plugin(
                \Hasnayeen\Themes\ThemesPlugin::make()
            );
    }
}
