<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Acumuladores extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Acumuladores';

    protected static ?string $slug = 'configuracoes/acumuladores';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.client.pages.acumulador';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('tipo-cfops')
                    ->schema([
                        Tabs\Tab::make('NFes')
                            ->schema([
                                Tabs::make('tipo-entrada')
                                    ->tabs([
                                        Tabs\Tab::make('Entrada Terceiro')
                                            ->schema([
                                                Livewire::make('organization.configuration.acumulador.nfe-entrada-terceiro-form'),
                                            ]),
                                        Tabs\Tab::make('Entrada Própria')
                                            ->schema([
                                                Livewire::make('organization.configuration.acumulador.nfe-entrada-propria-form'),
                                            ]),

                                    ]),
                            ]),
                        Tabs\Tab::make('CTes')
                            ->schema([
                                Tabs::make('tipo-entrada')
                                    ->tabs([
                                        Tabs\Tab::make('NFe de Entrada')
                                            ->schema([
                                                Livewire::make('organization.configuration.acumulador.cte-entrada-form'),
                                            ]),
                                        Tabs\Tab::make('NFe de Saida')
                                            ->schema([
                                                Livewire::make('organization.configuration.acumulador.cte-saida-form'),
                                            ]),

                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
