<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use App\Models\Tenant\Acumulador;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use App\Forms\Components\SelectTagGrouped;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\Configuracoes\ConfiguracaoFactory;

class AcumuladoresNfeTerceiroForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $acumuladores = $config->obterAcumuladoresTerceiroNfe();

        $this->form->fill($acumuladores);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('itens')
                    ->hiddenLabel()
                    ->schema([
                        SelectTagGrouped::make('codigo_acumulador')
                            ->label('Acumulador')
                            ->columnSpan(2)
                            ->multiple(false)
                            ->options(function () {
                                $acumuladores = Acumulador::where('organization_id', getOrganizationCached()->id)->get();
                                $options = [];
                                foreach ($acumuladores as $tagKey => $acumulador) {
                                    $tags['id'] = $acumulador->codi_acu;
                                    $tags['name'] = $acumulador->codi_acu . ' - ' . $acumulador->nome_acu;
                                    $options[$tagKey] = $tags;
                                }


                                return $options;
                            }),

                        SelectTagGrouped::make('tag_id')
                            ->label('Etiquetas')
                            ->columnSpan(2)
                            ->multiple(true)
                            ->options(function () {
                                $categoryTag = categoryWithTagForSearching(getOrganizationCached()->id);
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

                        TagsInput::make('cfops')
                            ->label('CFOPs Associados')
                            ->placeholder('Digite os CFOPs e pressione Enter')
                            ->separator(',')
                            ->splitKeys(['Enter', ',', ' '])
                            ->hint('Já convertido')
                            ->helperText('Digite um ou mais CFOPs associados a este acumulador')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
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
        $config->salvarAcumuladoresTerceiroNfe($formData);

        Notification::make()
            ->success()
            ->title('Acumuladores de entrada NFe salvos com sucesso')
            ->send();
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.acumuladores-nfe-terceiro-form');
    }
}
