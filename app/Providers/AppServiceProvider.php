<?php

namespace App\Providers;

use Filament\Tables\Table;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use App\Models\Tenant\UserPanelPermission;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Facades\FilamentAsset;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(TenantDatabaseManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePanelSwitch();

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): View => view('components.footer'),
        );

        Table::configureUsing(function (Table $table): void {
            $table

                ->filtersLayout(FiltersLayout::AboveContentCollapsible)
                ->paginationPageOptions([25, 50, 100]);
        });

        FilamentAsset::register([
            Js::make('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js'),
        ]);

        FilamentAsset::register([
            Css::make('tom-select', 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css'),
        ]);
    }

    protected function configurePanelSwitch(): void
    {
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch
                ->modalHeading('Escolha o módulo')
                ->modalWidth('md')
                ->panels(function () {

                    $user = Auth::user();

                    if ($user?->last_organization_id) {

                        $panels = UserPanelPermission::getUserPanels(Auth::user(), getOrganizationCached());

                        return array_values($panels ?? []);
                    }

                    return ['admin'];
                })
                ->iconSize(16)
                ->simple()
                ->renderHook('panels::sidebar.nav.start')
                ->labels([               
                    'ged' => __('GED'),
                    'fiscal' => __('Fiscal'),
                    'contabil' => __('Contabil'),
                ]);
        });
    }
}
