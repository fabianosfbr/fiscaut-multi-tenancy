<?php

namespace App\Providers;

use Filament\View\PanelsRenderHook;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;

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
        FilamentView::registerRenderHook(
            PanelsRenderHook::CONTENT_START,
            fn (): string => Blade::render('@livewire(\'component.choice-organization\')'),
        );

        $this->configurePanelSwitch();
    }

    protected function configurePanelSwitch(): void
    {
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch
                ->modalHeading('Escolha o módulo')
                ->modalWidth('md')
                ->excludes(['admin'])
                ->iconSize(16)
                ->simple();
        });
    }
}
