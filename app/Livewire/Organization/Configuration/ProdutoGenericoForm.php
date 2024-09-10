<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Tenant\EntradasProdutosGenerico;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\GrupoEntradasProdutosGenerico;
use Filament\Forms\Components\Section;

class ProdutoGenericoForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $values = GrupoEntradasProdutosGenerico::with('produtos')
            ->where('organization_id', auth()->user()->last_organization_id)
            ->select('id', 'tags')
            ->get()
            ->toArray();

        $this->form->fill([
            'configuracoes_produtos' => $values,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Repeater::make('configuracoes_produtos')
                            ->label('')
                            ->schema([
                                TagsInput::make('tags')
                                    ->label('Etiquetas')
                                    ->placeholder('Digite a nova etiqueta e tecle ENTER para adicionar')
                                    ->required(),
                                Repeater::make('produtos')
                                    ->label('')
                                    ->schema([
                                        TextInput::make('cod_produto')
                                            ->label('Cód. Produto')
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('descricao')
                                            ->label('Descrição')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('ncm')
                                            ->label('NCM')
                                            ->required()
                                            ->columnSpan(1),
                                    ])
                                    ->addActionLabel('Adicionar Produto')
                                    ->columns(4),

                            ])
                            ->collapsible()
                            ->addActionLabel('Adicionar Etiqueta'),
                    ])
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $values = $this->form->getState();

        $grupos = GrupoEntradasProdutosGenerico::get();

        foreach ($grupos as $key => $grupo) {
            EntradasProdutosGenerico::where('grupo_id', $grupo->id)->delete();
        }

        GrupoEntradasProdutosGenerico::truncate();

        foreach ($values['configuracoes_produtos'] as $key => $value) {
            $grupo = GrupoEntradasProdutosGenerico::create([
                'tags' => $value['tags'],
                'organization_id' => auth()->user()->last_organization_id,
            ]);

            foreach ($value['produtos'] as $key => $produto) {
                EntradasProdutosGenerico::create([
                    'cod_produto' => $produto['cod_produto'],
                    'descricao' => $produto['descricao'],
                    'ncm' => $produto['ncm'],
                    'grupo_id' => $grupo->id,
                ]);
            }
        }

        Cache::forget('grupo_entradas_produtos_genericos_' . auth()->user()->last_organization_id);

        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }
    public function render()
    {
        return view('livewire.organization.configuration.produto-generico-form');
    }
}
