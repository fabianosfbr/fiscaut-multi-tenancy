<?php

namespace App\Filament\Fiscal\Pages;

use App\Livewire\Organization\Configuration\Acumulador\CteEntradaForm as AcumuladorCteEntradaForm;
use App\Livewire\Organization\Configuration\Acumulador\CteSaidaForm as AcumuladorCteSaidaForm;
use App\Livewire\Organization\Configuration\Acumulador\NfeEntradaPropriaForm as AcumuladorNfeEntradaPropriaForm;
use App\Livewire\Organization\Configuration\Acumulador\NfeEntradaTerceiroForm as AcumuladorNfeEntradaTerceiroForm;
use App\Livewire\Organization\Configuration\ConfiguracoesGeraisForm;
use App\Livewire\Organization\Configuration\CteEntradaForm;
use App\Livewire\Organization\Configuration\CteSaidaForm;
use App\Livewire\Organization\Configuration\ImpostoEquivalenteEntradaForm;
use App\Livewire\Organization\Configuration\NfeEntradaPropriaForm;
use App\Livewire\Organization\Configuration\NfeEntradaTerceiroForm;
use App\Livewire\Organization\Configuration\ProdutoGenericoForm;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ConfiguracoesGerais extends Page
{
    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Configurações Gerais';

    protected static ?string $title = 'Configurações Gerais';

    protected static ?string $slug = 'configuracoes/configuracoes-gerais';

    protected static string $view = 'filament.fiscal.pages.configuracoes-gerais';

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
                                            ->schema([
                                                Tabs::make('Tabs')
                                                    ->tabs([
                                                        Tabs\Tab::make('NFe')
                                                            ->schema([
                                                                Tabs::make('nfe-tipo-entrada')
                                                                    ->contained(false)
                                                                    ->persistTabInQueryString('nfe-tab')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('Entrada Terceiro')
                                                                            ->schema([
                                                                                Livewire::make(NfeEntradaTerceiroForm::class),

                                                                            ]),
                                                                        Tabs\Tab::make('Entrada Própria')
                                                                            ->schema([
                                                                                Livewire::make(NfeEntradaPropriaForm::class),

                                                                            ]),

                                                                    ]),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
                                                            ->schema([
                                                                Tabs::make('cte-tipo-entrada')
                                                                    ->contained(false)
                                                                    ->persistTabInQueryString('cte-tab')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('NFe Entrada')
                                                                            ->schema([
                                                                                Livewire::make(component: CteEntradaForm::class),

                                                                            ]),
                                                                        Tabs\Tab::make('NFe Saída')
                                                                            ->schema([
                                                                                Livewire::make(component: CteSaidaForm::class),
                                                                            ]),

                                                                    ]),
                                                            ]),

                                                    ])
                                                    ->persistTabInQueryString('cfops-tab'),
                                            ]),

                                        Tabs\Tab::make('Acumuladores')
                                            ->schema([
                                                Tabs::make('Tabs')
                                                    ->tabs([
                                                        Tabs\Tab::make('NFe')
                                                            ->schema([
                                                                Tabs::make('nfe-tipo-entrada')
                                                                    ->contained(false)
                                                                    ->persistTabInQueryString('nfe-tab')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('Entrada Terceiro')
                                                                            ->schema([
                                                                                Livewire::make(AcumuladorNfeEntradaTerceiroForm::class),

                                                                            ]),
                                                                        Tabs\Tab::make('Entrada Própria')
                                                                            ->schema([
                                                                                Livewire::make(AcumuladorNfeEntradaPropriaForm::class),

                                                                            ]),

                                                                    ]),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
                                                            ->schema([
                                                                Tabs::make('cte-tipo-entrada')
                                                                    ->contained(false)
                                                                    ->persistTabInQueryString('cte-tab')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('NFe Entrada')
                                                                            ->schema([
                                                                                Livewire::make(component: AcumuladorCteEntradaForm::class),

                                                                            ]),
                                                                        Tabs\Tab::make('NFe Saída')
                                                                            ->schema([
                                                                                Livewire::make(component: AcumuladorCteSaidaForm::class),
                                                                            ]),

                                                                    ]),
                                                            ]),

                                                    ])
                                                    ->persistTabInQueryString('cfops-tab'),
                                            ]),
                                        Tabs\Tab::make('Produtos Genéricos')
                                            ->schema([
                                                Livewire::make(ProdutoGenericoForm::class),
                                            ]),
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
