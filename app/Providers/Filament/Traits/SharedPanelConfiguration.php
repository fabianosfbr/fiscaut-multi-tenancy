<?php

namespace App\Providers\Filament\Traits;

use Filament\Panel;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use App\Http\Middleware\CheckPanelAccess;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Client\Pages\Auth\LoginPage;
use Illuminate\Session\Middleware\StartSession;
use App\Filament\Client\Pages\Auth\RegisterPage;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Client\Pages\Auth\PasswordReset;
use App\Http\Middleware\CheckUserHasOrganization;
use App\Filament\Clusters\Profile\Pages\ViewProfile;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Filament\Client\Pages\Auth\EmailVerificationPrompt;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

trait SharedPanelConfiguration
{
    /**
     * Configurações base compartilhadas entre todos os painéis
     */
    protected function getSharedBaseConfiguration(Panel $panel): Panel
    {
        return $panel
            ->login(LoginPage::class)
           // ->registration(RegisterPage::class)
            ->emailVerification(EmailVerificationPrompt::class)
            ->passwordReset(
                resetAction: PasswordReset::class
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('images/application/logo-no-background.png'))
            ->brandLogoHeight('65px')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->maxContentWidth(MaxWidth::Full)
            ->breadcrumbs(false);
    }

    /**
     * Configurações de middleware compartilhadas entre todos os painéis
     */
    protected function getSharedMiddlewareConfiguration(Panel $panel): Panel
    {
        return $panel
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Configurações de middleware específicas para painéis do tenant
     */
    protected function getTenantMiddlewareConfiguration(Panel $panel): Panel
    {
        return $panel
            ->middleware([
                "web",
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
                CheckUserHasOrganization::class,
               // CheckPanelAccess::class,
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
            ], isPersistent: true);
    }

    /**
     * Configurações de plugins compartilhadas entre todos os painéis
     */
    protected function getSharedPluginsConfiguration(Panel $panel): Panel
    {
        return $panel
            ->plugins([
                \Hasnayeen\Themes\ThemesPlugin::make(),
            ]);
    }

    /**
     * Configuração para seleção de organização
     */
    protected function getOrganizationSelectorConfiguration(Panel $panel): Panel
    {
        return $panel
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn(): string => Blade::render('@livewire(\'component.choice-organization\')'),
            );
    }

    /**
     * Configuração para o menu de usuário
     */
    protected function getUserMenuConfiguration(Panel $panel): Panel
    {
        return $panel
            ->userMenuItems([
                MenuItem::make()
                    ->label('Meu Perfil')
                    ->icon('heroicon-o-user')
                    ->url(fn(): string => ViewProfile::getUrl()),
            ]);
    }
}
