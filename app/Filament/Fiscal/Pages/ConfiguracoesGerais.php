<?php

namespace App\Filament\Fiscal\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Livewire;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\Configuracoes\ConfiguracaoFactory;

class ConfiguracoesGerais extends Page implements HasForms
{
    use InteractsWithForms;

 
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $navigationLabel = 'Configurações Gerais';
    protected static ?string $title = 'Configurações Gerais';
    protected static ?string $slug = 'configuracoes-gerais';
    protected static string $view = 'filament.fiscal.pages.configuracoes-gerais';

    public ?array $data = [];

    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $configGerais = $config->obterConfiguracoesGerais();

        $this->form->fill($configGerais);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Configurações')
                    ->persistTab()
                    ->tabs([
                        Tabs\Tab::make('Geral')
                            ->schema([
                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\ConfiguracoesGeraisForm::class),
                            ]),
                        Tabs\Tab::make('Entrada')
                            ->schema([
                                Tabs::make('TiposEntrada')
                                    ->tabs([
                                        Tabs\Tab::make('CFOPs')
                                            ->schema([
                                                Tabs::make('Tabs')
                                                    ->tabs([
                                                        Tabs\Tab::make('NFe')
                                                            ->schema([
                                                                Tabs::make('TiposNFes')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('Notas de Terceiros')
                                                                            ->schema([
                                                                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsNfeForm::class, [
                                                                                    'tipoNota' => 'terceiros',
                                                                                    'tipoOperacao' => 'entrada'
                                                                                ])
                                                                                    ->id('cfops-terceiros'),
                                                                            ]),
                                                                        Tabs\Tab::make('Notas Próprias')
                                                                            ->schema([
                                                                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsNfeForm::class, [
                                                                                    'tipoNota' => 'propria',
                                                                                    'tipoOperacao' => 'entrada'
                                                                                ])
                                                                                    ->id('cfops-propria'),
                                                                            ]),
                                                                    ]),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
                                                            ->schema([
                                                                Tabs::make('TiposCTes')
                                                                    ->tabs([
                                                                        Tabs\Tab::make('Notas de Entrada')
                                                                            ->schema([
                                                                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsCteForm::class, [
                                                                                    'tipoOperacao' => 'entrada'
                                                                                ])
                                                                                    ->id('cfops-cte-entrada'),
                                                                            ]),

                                                                        Tabs\Tab::make('Notas de Saida')
                                                                            ->schema([
                                                                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsCteForm::class, [
                                                                                    'tipoOperacao' => 'saida'
                                                                                ])
                                                                                    ->id('cfops-cte-saida'),
                                                                            ]),
                                                                    ]),
                                                            ]),
                                                    ]),

                                            ]),
                                            Tabs\Tab::make('Acumuladores')
                                                ->schema([
                                                    Tabs::make('TiposAcumuladores')
                                                        ->tabs([
                                                            Tabs\Tab::make('NFe')
                                                                ->schema([
                                                                    Tabs::make('TiposNFes')
                                                                        ->tabs([
                                                                            Tabs\Tab::make('Notas de Terceiros')
                                                                                ->schema([
                                                                                    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\AcumuladoresNfeTerceiroForm::class)
                                                                                        ->id('acumuladores-nfe-terceiros'),
                                                                                ]),
                                                                            Tabs\Tab::make('Notas Próprias')
                                                                                ->schema([
                                                                                    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\AcumuladoresNfePropriaForm::class)
                                                                                        ->id('acumuladores-nfe-propria'),
                                                                                ]),
                                                                        ]),
                                                                ]),
                                                            Tabs\Tab::make('CTe')
                                                                ->schema([
                                                                    Tabs::make('TiposCTes')
                                                                        ->tabs([
                                                                            Tabs\Tab::make('Notas de Entrada')
                                                                                ->schema([
                                                                                    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\AcumuladoresCteEntradaForm::class)
                                                                                        ->id('acumuladores-cte-entrada'),
                                                                                ]),
                                                                            Tabs\Tab::make('Notas de Saída')
                                                                                ->schema([
                                                                                    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\AcumuladoresCteSaidaForm::class)
                                                                                        ->id('acumuladores-cte-saida-tab'),
                                                                                ]),
                                                                        ]),
                                                                ]),
                                                        ]),
                                                ]),
                                            Tabs\Tab::make('Impostos')
                                                ->schema([
                                                    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\ConfiguracoesImpostosForm::class)
                                                        ->id('configuracoes-impostos'),
                                                ]),
                                    ]),
                            ]),
                        
                    ])
            ])
            ->statePath('data');
    }
}
