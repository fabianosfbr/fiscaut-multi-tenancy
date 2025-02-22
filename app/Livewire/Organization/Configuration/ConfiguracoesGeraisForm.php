<?php

namespace App\Livewire\Organization\Configuration;

use App\Models\Tenant\ConfiguracaoGeral;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class ConfiguracoesGeraisForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Organization $organization;

    public $configuracoes;

    public function mount(): void
    {
        $this->configuracoes = ConfiguracaoGeral::getMany(auth()->user()->last_organization_id);

        $this->form->fill($this->configuracoes);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([

                                Grid::make(1)
                                    ->schema([
                                        Fieldset::make('')
                                            ->schema([
                                                Checkbox::make('isNfeClassificarNaEntrada')
                                                    ->label('Data de entrada na classificação da Nfe')
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->inline()
                                                    ->columnSpanFull(),
                                                Checkbox::make('isNfeManifestarAutomatica')
                                                    ->label('Manifestação automática pelo Fiscaut')
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->inline()
                                                    ->columnSpanFull(),
                                                Checkbox::make('isNfeClassificarSomenteManifestacao')
                                                    ->label('Classificação somente após manifestação')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                                Checkbox::make('isNfeMostrarEtiquetaComNomeAbreviado')
                                                    ->label('Mostra o código da etique ao invés do nome abreviado')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                                Checkbox::make('isNfeTomaCreditoIcms')
                                                    ->label('Considerar como crédito de ICMS as NF com CFOP 1.401')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->live()
                                                    ->columnSpanFull(),

                                                Select::make('tagsCreditoIcms')
                                                    ->label('Notas com as etiquetas abaixo serão consideradas como credito de ICMS')
                                                    ->columnSpan(2)
                                                    ->multiple(true)
                                                    ->options(function () {
                                                        // $categoryTag = categoryWithTagForSearching();

                                                        $tags = [];
                                                        // foreach ($categoryTag as $key => $category) {
                                                        //     foreach ($category->tags  as $tagKey => $tag) {
                                                        //         if (!$tag->is_enable) {
                                                        //             continue;
                                                        //         }

                                                        //         $tags[$tag->id] = $tag->code . ' - ' . $tag->name;
                                                        //     }
                                                        // }

                                                        return $tags;
                                                    })
                                                    ->required()
                                                    ->visible(function ($get) {
                                                        return $get('isNfeTomaCreditoIcms');
                                                    })
                                                    ->validationMessages([
                                                        'required' => 'É obrigatório informar as etiquetas para credito de ICMS',
                                                    ]),
                                                Checkbox::make('verificar_uf_emitente_destinatario')
                                                    ->label('CFOP: verificar UF emitente X UF destinatário')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                            ])->columnSpan(1),

                                        Fieldset::make('Produtos Genéricos')
                                            ->schema([
                                                Checkbox::make('importar-movimentos-dos-produtos')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->default(true)
                                                    ->columnSpanFull(),
                                                Checkbox::make('realizar-lancamento-de-produtos-genericos')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                            ])->columnSpan(1),

                                    ])->columnSpan(1),

                                Grid::make(1)
                                    ->schema([
                                        Fieldset::make('Cadastros')
                                            ->schema([
                                                Checkbox::make('cadastros-importar-registros-inexistentes')
                                                    ->label('Importar somente registros inexistentes')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                                TextInput::make('cst-padrao-ipi')
                                                    ->label('CST padrao IPI')
                                                    ->inlineLabel()
                                                    ->columnSpanFull(),
                                            ])->columnSpan(1),
                                        Fieldset::make('Movimentos')
                                            ->schema([
                                                Checkbox::make('movimentos-importar-registros-inexistentes')
                                                    ->label('Importar somente registros inexistentes')
                                                    ->inline()
                                                    ->dehydrateStateUsing(function ($state) {
                                                        return $state ? 'true' : 'false';
                                                    })
                                                    ->columnSpanFull(),
                                            ])->columnSpan(1),

                                    ])->columnSpan(1),

                            ]),
                    ]),

            ])
            ->statePath('data');
    }

    public function submit()
    {
        $values = $this->form->getState();

        $organizationId = auth()->user()?->last_organization_id;

        foreach ($values as $key => $value) {

            ConfiguracaoGeral::setValue($key, $value, $organizationId);
        }

        ConfiguracaoGeral::clearOrganizationCache($organizationId);

        Notification::make()
            ->success()
            ->title('O valores foram salvos com sucesso!')
            ->send();
    }

    public function render()
    {
        return view('livewire.organization.configuration.configuracoes-gerais-form');
    }
}
