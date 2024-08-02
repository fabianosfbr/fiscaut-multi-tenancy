<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Models\Tenant\Issuer;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use App\Models\Tenant\Organization;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Client\Pages\Auth\LoginPage;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Client\Pages\Auth\RegisterPage;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Client\Pages\Auth\PasswordReset;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\App\Pages\Tenancy\EditOrganizationPage;
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

            ->tenant(Organization::class)
            ->tenantProfile(EditOrganizationPage::class)
            ->tenantMenuItems([
                'profile' => MenuItem::make()
                //->visible(fn (): bool => auth()->user()->can('manage-organization'))
                ->label('Gerenciar empresa'),
                'register' => MenuItem::make()
                    ->label('Adicionar empresa')
                    ->url(fn (): string => '/app/'.Filament::getTenant()->id.'/new-organization'), // @phpstan-ignore-line,

            ])

            ->viteTheme('resources/css/filament/client/theme.css')
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\\Filament\\Client\\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\\Filament\\Client\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\\Filament\\Client\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('tenant');
    }
}
