<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Cfop;
use App\Models\Tenant\CategoryTag;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use App\Forms\Components\SelectTagGrouped;
use App\Models\Tenant\EntradasCfopsEquivalente;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Tenant\GrupoEntradasCfopsEquivalente;

class NfeEntradaPropriaForm extends Component implements HasForms
{
    use InteractsWithForms;
    private const TIPO = 'nfe-entrada-propria';

    public ?array $data = [];

    public $values;


    public function mount(): void
    {
        $this->values = GrupoEntradasCfopsEquivalente::with('cfops')
            ->whereHas('cfops', function ($query) {
                $query->where('tipo', self::TIPO);
            })
            ->where('organization_id', auth()->user()->last_organization_id)->get();

        $this->form->fill([
            'organization_cfop' => $this->values,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('organization_cfop')
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
                        Repeater::make('cfops')
                            ->label('')
                            ->schema([
                                Select::make('cfop_entrada')
                                    ->label('CFOP')
                                    ->searchable()
                                    ->required()
                                    ->options($this->cfopsForSearching())
                                    ->columnSpan(2),
                                TagsInput::make('valores')
                                    ->label('CFOP Saída')
                                    ->required()
                                    ->placeholder('Digite a nova etiqueta e tecle ENTER para adicionar')
                                    ->columnSpan(2),
                            ])
                            ->addActionLabel('Adicionar CFOP')
                            ->columns(4),

                    ])
                    ->collapsible()
                    ->addActionLabel('Adicionar Etiqueta'),

            ])
            ->statePath('data');
    }

    public function cfopsForSearching()
    {

        $tagData = Cfop::getAllForTag()->pluck('full_name', 'codigo');

        return $tagData;
    }

    public function submit()
    {
        $values = $this->form->getState();

        foreach ($this->values as $grupo) {
            EntradasCfopsEquivalente::where('grupo_id', $grupo->id)
                ->where('tipo', self::TIPO)
                ->delete();

            GrupoEntradasCfopsEquivalente::find($grupo->id)->delete();
        }




        foreach ($values['organization_cfop'] as $value) {
            $grupo = GrupoEntradasCfopsEquivalente::create([
                'tags' => $value['tags'],
                'organization_id' => auth()->user()->last_organization_id,
            ]);

            foreach ($value['cfops'] as $cfop) {
                EntradasCfopsEquivalente::create([
                    'cfop_entrada' => intval($cfop['cfop_entrada']),
                    'valores' => $cfop['valores'],
                    'tipo' => self::TIPO,
                    'grupo_id' => $grupo->id,
                ]);
            }
        }

        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }

    public function render()
    {
        return view('livewire.organization.configuration.nfe-entrada-propria-form');
    }
}
