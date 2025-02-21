<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Livewire;
use App\Livewire\Organization\Configuration\ProdutoGenericoForm;
use App\Livewire\Organization\Configuration\ConfiguracoesGeraisForm;
use App\Livewire\Organization\Configuration\ImpostoEquivalenteEntradaForm;

class ConfiguracoesGerais extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Configurações Gerais';

    protected static ?string $title = 'Configurações Gerais';

    protected static ?string $slug = 'configuracoes/configuracoes-gerais';

    protected static string $view = 'filament.client.pages.configuracoes-gerais';

    protected static ?int $navigationSort = 1;



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Heading')
                    ->tabs([
                        Tabs\Tab::make('Configurações')
                            ->schema([
                                Livewire::make(ConfiguracoesGeraisForm::class),
                            ]),
                        Tabs\Tab::make('Entradas')
                            ->schema([
                                Tabs::make('Heading')
                                    ->tabs([
                                        Tabs\Tab::make('Impostos')
                                            ->schema([
                                                Livewire::make(ImpostoEquivalenteEntradaForm::class),
                                            ]),
                                        Tabs\Tab::make('CFOP\'s')
                                            ->schema([]),
                                    ])
                                    ->persistTabInQueryString('settings-tab-entradas')
                                    ->contained(false),
                            ]),
                    ])
                    ->persistTabInQueryString('settings-tab'),
            ])
            ->statePath('data');
    }
}
