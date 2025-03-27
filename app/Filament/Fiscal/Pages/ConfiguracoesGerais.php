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

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Configurações';
    protected static ?string $title = 'Configurações Gerais';
    protected static ?string $slug = 'configuracoes-gerais';
    protected static ?string $navigationGroup = 'Sistema';
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
                    ->tabs([
                        Tabs\Tab::make('Geral')
                            ->schema([
                                Section::make('Configurações Gerais')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Checkbox::make('nfe_classificacao_data_entrada')
                                                    ->label('Exibir Data de Entrada na classificação da NFe')
                                                    ->helperText('Quando ativado, permite informar a data de entrada ao classificar uma NFe'),

                                                Checkbox::make('manifestacao_automatica')
                                                    ->label('Manifestação automática pelo Fiscaut')
                                                    ->helperText('Quando ativado, o sistema realizará a manifestação automática das notas'),

                                                Checkbox::make('mostrar_codigo_etiqueta')
                                                    ->label('Mostrar código da etiqueta ao invés do nome abreviado')
                                                    ->helperText('Quando ativado, o sistema mostrará o código da etiqueta ao invés do nome abreviado'),
                                                
                                                Checkbox::make('icms_credito_cfop_1401')
                                                    ->label('Considerar como crédito de ICMS as NF com CFOP 1.401')
                                                    ->helperText('Quando ativado, o sistema considerará crédito de ICMS para notas com CFOP 1.401'),
                                                
                                                Checkbox::make('cfop_verificar_uf')
                                                    ->label('Verificar UF no processamento de CFOP')
                                                    ->helperText('Quando ativado, verifica a UF do emitente e destinatário para processar os CFOPs corretamente'),
                                            ]),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Entrada')
                            ->schema([
                                Tabs::make('TiposEntrada')
                                    ->tabs([
                                        Tabs\Tab::make('CFOPs')
                                            ->schema([
                                                Tabs::make('TiposCFOPs')
                                                    ->tabs([
                                                        Tabs\Tab::make('NFe')
                                                            ->schema([
                                                            //    Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\CfopsNfeEntradaForm::class),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
                                                            ->schema([
                                                                // Implementaremos os formulários específicos mais tarde
                                                            ]),
                                                    ]),
                                            ]),
                                        
                                        Tabs\Tab::make('Acumuladores')
                                            ->schema([
                                                Tabs::make('TiposAcumuladores')
                                                    ->tabs([
                                                        Tabs\Tab::make('NFe')
                                                            ->schema([
                                                             //   Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\AcumuladoresNfeEntradaForm::class),
                                                            ]),
                                                        Tabs\Tab::make('CTe')
                                                            ->schema([
                                                                // Implementaremos os formulários específicos mais tarde
                                                            ]),
                                                    ]),
                                            ]),
                                        
                                        Tabs\Tab::make('Produtos Genéricos')
                                            ->schema([
                                              //  Livewire::make(\App\Filament\Fiscal\Pages\Configuracoes\ProdutosGenericosForm::class),
                                            ]),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Saída')
                            ->schema([
                                // Implementaremos os formulários específicos mais tarde
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $formData = $this->form->getState();
        $organizationId = Auth::user()->last_organization_id;
        
        // Salva as configurações gerais
        $config = ConfiguracaoFactory::criar($organizationId);
        $config->salvarConfiguracoesGerais($formData);
        
        Notification::make()
            ->success()
            ->title('Configurações salvas com sucesso')
            ->send();
    }
}
