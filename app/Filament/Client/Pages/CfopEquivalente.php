<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Components\Livewire;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;

class CfopEquivalente extends Page
{

    protected static string $view = 'filament.client.pages.cfop-equivalente';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'CFOP\'s Equivalentes';

    protected static ?string $slug = 'configuracoes/cfops-equivalentes';


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
                                                Livewire::make('organization.configuration.nfe-entrada-terceiro-form'),
                                            ]),
                                        Tabs\Tab::make('Entrada Própria')
                                            ->schema([
                                                Livewire::make('organization.configuration.nfe-entrada-propria-form'),
                                            ]),

                                    ])
                            ]),
                        Tabs\Tab::make('CTes')
                            ->schema([
                                Tabs::make('tipo-entrada')
                                    ->tabs([
                                        Tabs\Tab::make('NFe de Entrada')
                                            ->schema([
                                                Livewire::make('organization.configuration.cte-entrada-form'),
                                            ]),
                                        Tabs\Tab::make('NFe de Saida')
                                            ->schema([
                                                Livewire::make('organization.configuration.cte-saida-form'),
                                            ]),

                                    ])
                            ]),
                    ])
            ])
            ->statePath('data');
    }
}
