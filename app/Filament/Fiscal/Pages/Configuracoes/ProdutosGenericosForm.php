<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use App\Models\Tenant\Tag;
use App\Services\Configuracoes\ConfiguracaoFactory;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class ProdutosGenericosForm extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $produtosGenericos = $config->obterProdutosGenericos();
        
        $this->form->fill($produtosGenericos);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('instrucoes')
                    ->content('Configure abaixo os produtos genéricos agrupados por etiquetas')
                    ->columnSpanFull(),
                
                Repeater::make('grupos')
                    ->schema([
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
                        
                        Repeater::make('produtos')
                            ->label('Produtos')
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(30)
                                    ->columnSpan(1),
                                
                                TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(120)
                                    ->columnSpan(3),
                                
                                TextInput::make('ncm')
                                    ->label('NCM')
                                    ->required()
                                    ->mask('9999.99.99')
                                    ->placeholder('0000.00.00')
                                    ->columnSpan(1),
                                
                                TextInput::make('unidade')
                                    ->label('Unidade')
                                    ->required()
                                    ->maxLength(6)
                                    ->placeholder('UN')
                                    ->columnSpan(1),
                            ])
                            ->columns(6)
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['codigo'], $state['descricao']) 
                                    ? "{$state['codigo']} - {$state['descricao']}" 
                                    : null)
                            ->addActionLabel('Adicionar Produto')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->itemLabel(fn (array $state): ?string => 
                        !empty($state['tag_ids']) ? "Etiquetas: " . count($state['tag_ids']) : "Sem etiquetas")
                    ->addActionLabel('Adicionar Grupo')
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
        $config->salvarProdutosGenericos($formData);
        
        Notification::make()
            ->success()
            ->title('Produtos genéricos salvos com sucesso')
            ->send();
    }
    
    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.produtos-genericos-form');
    }
} 