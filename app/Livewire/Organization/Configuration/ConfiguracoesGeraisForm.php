<?php

namespace App\Livewire\Organization\Configuration;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

class ConfiguracoesGeraisForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Organization $organization;

    public function mount(): void
    {

        $this->organization = Organization::find(auth()->user()->last_organization_id);

       // dd($this->organization->toArray());
        $this->form->fill($this->organization->toArray());
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Checkbox::make('isNfeClassificarNaEntrada')
                            ->label('Data de entrada na classificação da Nfe')
                            ->inline()
                            ->columnSpanFull(),
                        Checkbox::make('isNfeManifestarAutomatica')
                            ->label('Manifestação automática pelo Fiscaut')
                            ->default(false)
                            ->inline()
                            ->columnSpanFull(),
                        Checkbox::make('isNfeClassificarSomenteManifestacao')
                            ->label('Classificação somente após manifestação')
                            ->default(false)
                            ->inline()
                            ->columnSpanFull(),
                        Checkbox::make('isNfeMostrarEtiquetaComNomeAbreviado')
                            ->label('Mostra o código da etique ao invés do nome abreviado')
                            ->default(false)
                            ->inline()
                            ->columnSpanFull(),
                        Checkbox::make('isNfeTomaCreditoIcms')
                            ->label('Considerar como crédito de ICMS as NF com CFOP 1.401')
                            ->default(false)
                            ->inline()
                            ->live()
                            ->columnSpanFull(),
                        TagsInput::make('tagsCreditoIcms')
                            ->label('Notas com as etiquetas abaixo serão consideradas como credito de ICMS')
                            ->required()
                            ->placeholder('Digite a nova etiqueta e tecle ENTER para adicionar')
                            ->required()
                            ->visible(function ($get) {
                                return $get('isNfeTomaCreditoIcms');
                            })
                            ->validationMessages([
                                'required' => 'É obrigatório informar as etiquetas para credito de ICMS',
                            ]),
                    ])

            ])
            ->statePath('data');
    }

    public function submit()
    {
        $values = $this->form->getState();

        $this->organization->update($values);

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
