<?php

namespace App\Livewire\Organization\Configuration\Acumulador;

use App\Forms\Components\SelectTagGrouped;
use App\Models\Tenant\Acumulador;
use App\Models\Tenant\CategoryTag;
use App\Models\Tenant\EntradasAcumuladorEquivalente;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class CteSaidaForm extends Component implements HasForms
{
    use InteractsWithForms;

    private const TIPO = 'cte-saida';

    public ?array $data = [];

    public function mount(): void
    {
        $values = EntradasAcumuladorEquivalente::where('tipo', self::TIPO)
            ->where('organization_id', auth()->user()->last_organization_id)
            ->get()->toArray();

        $this->form->fill([
            'configuracoes_acumuladores' => $values,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('configuracoes_acumuladores')
                    ->label('')
                    ->schema([
                        SelectTagGrouped::make('etiqueta_entrada')
                            ->label('Acumulador')
                            ->columnSpan(5)
                            ->multiple(false)
                            ->options(function () {
                                $acumuladores = Acumulador::getAll(auth()->user()->last_organization_id);
                                foreach ($acumuladores as $tagKey => $acumulador) {
                                    $tags['id'] = $acumulador->codi_acu;
                                    $tags['name'] = $acumulador->codi_acu.' - '.$acumulador->nome_acu;
                                    $options[$tagKey] = $tags;
                                }

                                return $options ?? [];
                            }),
                        SelectTagGrouped::make('valores')
                            ->label('Etiqueta')
                            ->columnSpan(1)
                            ->multiple(true)
                            ->options(function () {
                                $categoryTag = CategoryTag::getAllEnabled(auth()->user()->last_organization_id);

                                foreach ($categoryTag as $key => $category) {
                                    $tags = [];
                                    foreach ($category->tags as $tagKey => $tag) {
                                        if (! $tag->is_enable) {
                                            continue;
                                        }
                                        $tags[$tagKey]['id'] = $tag->id;
                                        $tags[$tagKey]['name'] = $tag->code.' - '.$tag->name;
                                    }
                                    $tagData[$key]['text'] = $category->name;
                                    $tagData[$key]['children'] = $tags;
                                }

                                return $tagData ?? [];
                            }),
                        TagsInput::make('cfops')
                            ->label('CFOPs')
                            ->hint('CFOP já convertido')
                            ->placeholder('Digite o CFOP e tecle ENTER para adicionar')
                            ->columnSpan(1),

                    ])
                    ->columns(3)
                    ->addActionLabel('Adicionar Acumulador'),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $values = $this->form->getState();

        EntradasAcumuladorEquivalente::where('tipo', self::TIPO)
            ->where('organization_id', auth()->user()->last_organization_id)
            ->delete();

        foreach ($values['configuracoes_acumuladores'] as $value) {

            EntradasAcumuladorEquivalente::create([
                'etiqueta_entrada' => $value['etiqueta_entrada'],
                'organization_id' => auth()->user()->last_organization_id,
                'valores' => $value['valores'],
                'cfops' => $value['cfops'],
                'tipo' => self::TIPO,
            ]);
        }

        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }

    public function render()
    {
        return view('livewire.organization.configuration.acumulador.cte-saida-form');
    }
}
