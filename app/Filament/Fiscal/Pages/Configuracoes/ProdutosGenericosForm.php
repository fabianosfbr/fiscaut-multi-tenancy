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
use App\Forms\Components\SelectTagGrouped;

class ProdutosGenericosForm extends Component implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $configuracoes = $config->obterProdutosGenericos();
        
        $this->form->fill($configuracoes);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('itens')
                    ->hiddenLabel()
                    ->schema([
                        SelectTagGrouped::make('tag_id')
                            ->label('Etiqueta')
                            ->columnSpan(1)
                            ->required()
                            ->options(function () {
                                $categoryTag = categoryWithTagForSearching(getOrganizationCached()->id);
                                $tagData = [];
                                foreach ($categoryTag as $key => $category) {
                                    $tags = [];
                                    foreach ($category->tags as $tagKey => $tag) {
                                        if (!$tag->is_enable) {
                                            continue;
                                        }
                                        $tags[$tagKey]['id'] = $tag->id;
                                        $tags[$tagKey]['name'] = $tag->code . ' - ' . $tag->name;
                                    }
                                    $tagData[$key]['text'] = $category->name;
                                    $tagData[$key]['children'] = $tags;
                                }
                                return $tagData ?? [];
                            }),
                        
                        Repeater::make('produtos')
                            ->label('Produtos')
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código do Produto')
                                    ->required()
                                    ->maxLength(20)
                                    ->columnSpan(1),
                                    
                                TextInput::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->maxLength(120)
                                    ->columnSpan(1),
                                    
                                TextInput::make('ncm')
                                    ->label('NCM')
                                    ->required()
                                    ->maxLength(8)
                                    ->mask('99999999')
                                    ->placeholder('00000000')
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->addActionLabel('Adicionar Produto')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->addActionLabel('Adicionar Etiqueta')
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