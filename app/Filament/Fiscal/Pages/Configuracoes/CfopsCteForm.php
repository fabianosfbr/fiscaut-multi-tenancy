<?php

namespace App\Filament\Fiscal\Pages\Configuracoes;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use App\Models\Tenant\Cfop;
use App\Models\Tenant\CategoryTag;
use Filament\Forms\Components\Hidden;
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

class CfopsCteForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    // Propriedade para rastrear o tipo de nota
    public string $tipoNota = 'entrada';

    // Propriedade para rastrear o tipo de operação (entrada ou saída)
    public string $tipoOperacao = 'entrada';

    public function mount(): void
    {
        // Carrega as configurações para o tipo definido
        $config = ConfiguracaoFactory::atual();

        if ($this->tipoOperacao === 'entrada') {
            
            $cfops = $config->obterCfopsEntradaCte(['tipo' => $this->tipoNota]);
        } else {
            
            $cfops = $config->obterCfopsSaidaCte(['tipo' => $this->tipoNota]);
        }

        // Define o tipo de nota no formulário
        $cfops['tipo_nota'] = $this->tipoNota;
        $cfops['tipo_operacao'] = $this->tipoOperacao;

    
        $this->form->fill($cfops);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $formData = $this->form->getState();

            // Identifica o tipo de operação
            $tipoOperacao = $formData['tipo_operacao'] ?? $this->tipoOperacao;
            $operacaoDescricao = $tipoOperacao === 'entrada' ? 'entrada' : 'saída';

            // Preserva o tipo nos dados salvos
            $formData['operacao'] = $tipoOperacao;

            // Remove os campos não necessários
            if (isset($formData['tipo_operacao'])) {
                unset($formData['tipo_operacao']);
            }

            // Validação básica dos itens
            if ($tipoOperacao === 'entrada' && empty($formData['itens'])) {
                throw new \Exception('Nenhum item foi adicionado ao formulário.');
            }
            
            if ($tipoOperacao === 'saida' && empty($formData['cfops'])) {
                throw new \Exception('Nenhum CFOP foi adicionado ao formulário.');
            }

            // Preparar os dados para salvar para entrada
            if ($tipoOperacao === 'entrada') {
                foreach ($formData['itens'] as $key => $item) {
                    // Verificar se tem cfops válidos
                    if (empty($item['cfops'])) {
                        throw new \Exception('Todos os itens devem ter pelo menos um CFOP.');
                    }
                }
            }

            // Salva as configurações
            $config = ConfiguracaoFactory::atual();

            if ($tipoOperacao === 'entrada') {
                $config->salvarCfopsEntradaCte($formData);
            } else {
                $config->salvarCfopsSaidaCte($formData);
            }

            Notification::make()
                ->success()
                ->title("CFOPs de {$operacaoDescricao} CTe salvos com sucesso")
                ->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao salvar CFOPs de CTe: " . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Erro ao salvar')
                ->body($e->getMessage())
                ->send();
        }
    }

    private function getFormSchema(): array
    {
        if ($this->tipoOperacao === 'entrada') {
            return [
                Hidden::make('tipo_operacao')
                    ->columnSpanFull(),

                Repeater::make('itens')
                    ->hiddenLabel()
                    ->schema([
                        // Campo de etiquetas (opcional)
                        SelectTagGrouped::make('tag_ids')
                            ->hiddenLabel()
                            ->columnSpan(2)
                            ->multiple(true)
                            ->required()
                            ->options(function () {
                                $categoryTag = CategoryTag::getAllEnabled(getOrganizationCached()->id);
                                $tagData = [];
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
                            }),

                        Repeater::make('cfops')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('cfop_entrada')
                                    ->label('CFOP')
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
                                    ->label('CFOPs de Saída')
                                    ->placeholder('Digite os CFOPs e pressione Enter')
                                    ->separator(',')
                                    ->splitKeys(['Enter', ',', ' '])
                                    ->helperText('Digite um ou mais CFOPs')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->addActionLabel('Adicionar CFOP')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Adicionar Etiqueta')
                    ->reorderable()
                    ->collapsible()
                    ->columnSpanFull(),
            ];
        }

        return [
            Hidden::make('tipo_operacao')
                ->columnSpanFull(),
                
            Repeater::make('cfops')
                ->hiddenLabel()
                ->schema([
                    Select::make('cfop_entrada')
                        ->label('CFOP')
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
                        ->label('CFOPs de Saída')
                        ->placeholder('Digite os CFOPs e pressione Enter')
                        ->separator(',')
                        ->splitKeys(['Enter', ',', ' '])
                        ->helperText('Digite um ou mais CFOPs')
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->addActionLabel('Adicionar CFOP')
                ->reorderable()
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.cfops-cte-form');
    }
}
