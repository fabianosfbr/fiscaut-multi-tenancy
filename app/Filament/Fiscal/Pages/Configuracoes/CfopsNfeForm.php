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

class CfopsNfeForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    // Propriedade para rastrear o tipo de nota
    public string $tipoNota = 'terceiro';

    // Propriedade para rastrear o tipo de operação (entrada ou saída)
    public string $tipoOperacao = 'entrada';

    public function mount(): void
    {
        // Carrega as configurações para o tipo definido
        $config = ConfiguracaoFactory::atual();

        if ($this->tipoOperacao === 'entrada') {
            $cfops = $config->obterCfopsEntradaNfe(['tipo' => $this->tipoNota]);
        } else {
            $cfops = $config->obterCfopsSaidaNfe(['tipo' => $this->tipoNota]);
        }

        // Define o tipo de nota no formulário
        $cfops['tipo_nota'] = $this->tipoNota;
        $cfops['tipo_operacao'] = $this->tipoOperacao;

        $this->form->fill($cfops);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Hidden::make('tipo_nota')
                    ->columnSpanFull(),

                Hidden::make('tipo_operacao')
                    ->columnSpanFull(),

                Repeater::make('itens')
                    ->hiddenLabel()
                    ->schema([
                        SelectTagGrouped::make('tag_id')
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
                            ->hiddenLabel()
                            ->schema([
                                Select::make('cfop_entrada')
                                    ->label(function () {
                                        return $this->tipoOperacao === 'entrada' ? 'CFOP Entrada' : 'CFOP Saída';
                                    })
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
                                    ->label(function () {
                                        return $this->tipoOperacao === 'entrada' ? 'CFOPs Saída' : 'CFOPs Entrada';
                                    })
                                    ->placeholder('Digite os CFOPs e pressione Enter')
                                    ->separator(',')
                                    ->splitKeys(['Enter', ',', ' '])
                                    ->helperText(function () {
                                        return $this->tipoOperacao === 'entrada'
                                            ? 'Digite um ou mais CFOPs de saída'
                                            : 'Digite um ou mais CFOPs de entrada';
                                    })
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
                    !empty($state['tag_id']) ? "Etiquetas: " . count($state['tag_id']) : "Sem etiquetas")
                    ->addActionLabel('Adicionar Etiqueta')
                    ->reorderable()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $formData = $this->form->getState();

            // Identifica o tipo de configuração e operação
            $tipoNota = $formData['tipo_nota'] ?? $this->tipoNota;
            $tipoOperacao = $formData['tipo_operacao'] ?? $this->tipoOperacao;
            $tipoDescricao = $tipoNota === 'terceiro' ? 'terceiro' : 'própria';
            $operacaoDescricao = $tipoOperacao === 'entrada' ? 'entrada' : 'saída';

            // Preserva o tipo nos dados salvos
            $formData['tipo'] = $tipoNota;
            $formData['operacao'] = $tipoOperacao;
         

            // Remove os campos não necessários
            if (isset($formData['tipo_nota'])) {
                unset($formData['tipo_nota']);
            }
            if (isset($formData['tipo_operacao'])) {
                unset($formData['tipo_operacao']);
            }

            // Validação básica dos itens
            if (empty($formData['itens'])) {
                throw new \Exception('Nenhum item foi adicionado ao formulário.');
            }

                 
            // Preparar os dados para salvar
            foreach ($formData['itens'] as $key => $item) {
                // Verificar se tem tag_id e cfops válidos
                if (empty($item['tag_id']) || empty($item['cfops'])) {
                    throw new \Exception('Todos os itens devem ter pelo menos uma etiqueta e um CFOP.');
                }
            }

          
            // Salva as configurações
            $config = ConfiguracaoFactory::atual();

            if ($tipoOperacao === 'entrada') {
            
                $config->salvarCfopsEntradaNfe($formData);
            } else {
                $config->salvarCfopsSaidaNfe($formData);
            }

            Notification::make()
                ->success()
                ->title("CFOPs de {$operacaoDescricao} NFe ({$tipoDescricao}) salvos com sucesso")
                ->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao salvar CFOPs: " . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Erro ao salvar')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function render()
    {
        return view('filament.fiscal.pages.configuracoes.cfops-nfe-form');
    }
}
