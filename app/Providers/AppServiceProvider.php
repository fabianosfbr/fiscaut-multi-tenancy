<?php

namespace App\Providers;


use Filament\Tables\Table;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Enums\FiltersLayout;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Facades\FilamentAsset;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePanelSwitch();

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
                ->excludes(['admin'])
                ->iconSize(16)
                ->simple()
                ->labels([
                    'client' => __('Configuração'),
                    'fiscal' => __('Fiscal'),
                    'contabil' => __('Contabil'),
                ]);;
        });
    }
}
