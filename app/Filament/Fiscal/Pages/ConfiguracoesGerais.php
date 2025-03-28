<?php

namespace App\Filament\Fiscal\Pages;

use App\Services\Configuracoes\ConfiguracaoFactory;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ConfiguracoesGerais extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurações';
    protected static ?string $title = 'Configurações Gerais';
    protected static ?string $slug = 'configuracoes-gerais';
    protected static ?string $navigationGroup = 'Fiscal';
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
                                    ]),
                            ]),
                        Tabs\Tab::make('Saída')
                            ->schema([
                                Tabs::make('TiposSaida')
                                    ->tabs([
                                        Tabs\Tab::make('CFOPs')
                                            ->schema([
                                                Tabs::make('TiposCFOPs')
                                                    ->tabs([
                                                        Tabs\Tab::make('Notas de Saída')
                                                            ->schema([
                                                                Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsNfeForm::class, [
                                                                    'tipoNota' => 'terceiros',
                                                                    'tipoOperacao' => 'saida'
                                                                ])
                                                                    ->id('cfops-saida'),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
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
                    ])
            ])
            ->statePath('data');
    }
}
