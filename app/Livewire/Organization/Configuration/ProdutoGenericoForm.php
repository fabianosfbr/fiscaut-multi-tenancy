<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\CategoryTag;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Forms\Components\SelectTagGrouped;
use App\Models\Tenant\EntradasProdutosGenerico;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\GrupoEntradasProdutosGenerico;

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
                                SelectTagGrouped::make('tags')
                                    ->label('Etiqueta')
                                    ->columnSpan(1)
                                    ->multiple(true)
                                    ->options(function () {
                                        $categoryTag = CategoryTag::getAllEnabled(auth()->user()->last_organization_id);

                                        foreach ($categoryTag as $key => $category) {
                                            $tags = [];
                                            foreach ($category->tags  as $tagKey => $tag) {
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
                            ->addActionLabel('Adicionar Produto Genérico'),
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
