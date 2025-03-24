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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt;

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
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->plugins([
                \Hasnayeen\Themes\ThemesPlugin::make(),
               // HooksHelperPlugin::make(),
            ]);
    }
}
