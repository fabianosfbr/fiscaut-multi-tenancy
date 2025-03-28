<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use Livewire\Component;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\Configuracoes\ConfiguracaoFactory;

class ConfiguracoesGeraisForm extends Component implements HasForms
{
    use InteractsWithForms;

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
                Section::make('Configurações Gerais')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Checkbox::make('nfe_classificacao_data_entrada')
                                    ->label('Data de Entrada na classificação da NFe')
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
           
            $formData = $this->form->getState();
            
            // Salva as configurações
            $config = ConfiguracaoFactory::atual();
            $config->salvarConfiguracoesGerais($formData);
            
            Notification::make()
                ->success()
                ->title("Configurações gerais salvas com sucesso")
                ->body("As configurações gerais foram salvas com sucesso")
                ->send();
                
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao salvar configurações gerais: " . $e->getMessage());
            
            Notification::make()
                ->danger()
                ->title('Erro ao salvar')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.configuracoes-gerais-form');
    }

   
} 