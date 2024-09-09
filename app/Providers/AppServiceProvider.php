<?php

namespace App\Providers;


use BezhanSalleh\PanelSwitch\PanelSwitch;
use Illuminate\Support\ServiceProvider;

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
