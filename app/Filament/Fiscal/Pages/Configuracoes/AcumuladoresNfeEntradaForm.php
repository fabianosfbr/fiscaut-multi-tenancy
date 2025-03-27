<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use App\Models\Tenant\Acumulador;
use App\Models\Tenant\Tag;
use App\Services\Configuracoes\ConfiguracaoFactory;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class AcumuladoresNfeEntradaForm extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $acumuladores = $config->obterAcumuladoresEntradaNfe();
        
        $this->form->fill($acumuladores);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('instrucoes')
                    ->content('Configure abaixo os acumuladores de entrada NFe com suas etiquetas e CFOPs associados')
                    ->columnSpanFull(),
                
                Repeater::make('itens')
                    ->schema([
                        Select::make('codigo_acumulador')
                            ->label('Código do Acumulador')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function () {
                                return Acumulador::all()->pluck('descricao', 'codigo');
                            })
                            ->columnSpan(2),
                        
                        Select::make('tag_ids')
                            ->label('Etiquetas')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->options(function () {
                                return Tag::all()->pluck('name', 'id')->toArray();
                            })
                            ->columnSpan(2),
                        
                        TagsInput::make('cfops')
                            ->label('CFOPs Associados')
                            ->placeholder('Digite os CFOPs e pressione Enter')
                            ->separator(',')
                            ->splitKeys(['Enter', ',', ' '])
                            ->helperText('Digite um ou mais CFOPs associados a este acumulador')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['codigo_acumulador']) ? "Acumulador: {$state['codigo_acumulador']}" : null)
                    ->addActionLabel('Adicionar Acumulador')
                    ->reorderable()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $formData = $this->form->getState();
        
        // Salva as configurações
        $config = ConfiguracaoFactory::atual();
        $config->salvarAcumuladoresEntradaNfe($formData);
        
        Notification::make()
            ->success()
            ->title('Acumuladores de entrada NFe salvos com sucesso')
            ->send();
    }
    
    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.acumuladores-nfe-entrada-form');
    }
} 