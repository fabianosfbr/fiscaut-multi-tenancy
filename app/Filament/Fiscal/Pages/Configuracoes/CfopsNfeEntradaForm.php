<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use App\Models\Tenant\Cfop;
use App\Models\Tenant\CategoryTag;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use App\Forms\Components\SelectTagGrouped;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\Configuracoes\ConfiguracaoFactory;

class CfopsNfeEntradaForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $cfops = $config->obterCfopsEntradaNfe();

        $this->form->fill($cfops);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('instrucoes')
                    ->content('Configure abaixo os CFOPs de entrada e saída agrupados por etiquetas')
                    ->columnSpanFull(),

                Repeater::make('itens')
                    ->schema([

                        SelectTagGrouped::make('tag_ids')
                            ->label('Etiqueta')
                            ->columnSpan(1)
                            ->multiple(true)
                            ->options(function () {
                                $categoryTag = CategoryTag::getAllEnabled(getOrganizationCached()->id);
                                foreach ($categoryTag as $key => $category) {
                                    $tags = [];
                                    foreach ($category->tags as $tagKey => $tag) {
                                        if (! $tag->is_enable) {
                                            continue;
                                        }
                                        $tags[$tagKey]['id'] = $tag->id;
                                        $tags[$tagKey]['name'] = $tag->code . ' - ' . $tag->name;
                                    }
                                    $tagData[$key]['text'] = $category->name;
                                    $tagData[$key]['children'] = $tags;
                                }

                                return $tagData ?? [];
                            })
                            ->columnSpan(2),

                        Repeater::make('cfops')
                            ->label('CFOPs')
                            ->schema([
                                Select::make('cfop_entrada')
                                    ->label('CFOP Entrada')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->options(function () {

                                        return Cfop::getAllForTag()
                                            ->pluck('full_name', 'codigo')
                                            ->toArray();
                                    })
                                    ->columnSpan(2),

                                TagsInput::make('cfops_saida')
                                    ->label('CFOPs Saída')
                                    ->placeholder('Digite os CFOPs e pressione Enter')
                                    ->separator(',')
                                    ->splitKeys(['Enter', ',', ' '])
                                    ->helperText('Digite um ou mais CFOPs de saída')
                                    ->columnSpan(2),

                                Checkbox::make('aplicar_uf_diferente')
                                    ->label('Aplicar UF diferente')
                                    ->helperText('Aplicar quando UF do emitente for diferente da UF do destinatário')
                                    ->inline()
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->itemLabel(fn(array $state): ?string =>
                            isset($state['cfop_entrada']) ? "CFOP: {$state['cfop_entrada']}" : null)
                            ->addActionLabel('Adicionar CFOP')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->itemLabel(fn(array $state): ?string =>
                    !empty($state['tag_ids']) ? "Etiquetas: " . count($state['tag_ids']) : "Sem etiquetas")
                    ->addActionLabel('Adicionar Grupo de Etiquetas')
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
        $config->salvarCfopsEntradaNfe($formData);

        Notification::make()
            ->success()
            ->title('CFOPs de entrada NFe salvos com sucesso')
            ->send();
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.cfops-nfe-entrada-form');
    }
}
