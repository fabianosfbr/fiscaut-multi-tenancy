<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Forms\Components\SelectTagGrouped;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\Configuracoes\ConfiguracaoFactory;

class ConfiguracoesImpostosForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $config = ConfiguracaoFactory::atual();
        $configuracoes = $config->obterConfiguracoesImpostos();

        $this->form->fill($configuracoes);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('itens')
                    ->hiddenLabel()
                    ->schema([
                        SelectTagGrouped::make('tag_ids')
                            ->label('Etiquetas')
                            ->columnSpan(1)
                            ->multiple(false)
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
                                return $tagData;
                            }),

                        Checkbox::make('zerar_ipi')
                            ->label('Zerar IPI')
                            ->helperText('Marque esta opção para zerar o IPI nas notas com esta configuração')
                            ->columnSpan(1),

                        Checkbox::make('zerar_icms')
                            ->label('Zerar ICMS')
                            ->helperText('Marque esta opção para zerar o ICMS nas notas com esta configuração')
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->addActionLabel('Adicionar Imposto')
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
        $config->salvarConfiguracoesImpostos($formData);

        Notification::make()
            ->success()
            ->title('Configurações de impostos salvas com sucesso')
            ->send();
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.configuracoes-impostos-form');
    }
} 