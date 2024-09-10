<?php

namespace App\Livewire\Organization;

use Exception;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Livewire;
use Filament\Notifications\Notification;
use App\Services\Tenant\OrganizationService;
use Filament\Forms\Concerns\InteractsWithForms;

class ConfigurationOrganizationForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public mixed $organization;



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('general-settings')
                            ->label('Configurações Gerais')
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

                                Select::make('tagsCreditoIcms')
                                    ->label('Notas com as etiquetas abaixo serão consideradas como credito de ICMS')
                                    ->columnSpan(2)
                                    ->multiple(true)
                                    ->options(function () {
                                        // $categoryTag = categoryWithTagForSearching();

                                        // $tags = [];
                                        // foreach ($categoryTag as $key => $category) {
                                        //     foreach ($category->tags  as $tagKey => $tag) {
                                        //         if (!$tag->is_enable) {
                                        //             continue;
                                        //         }

                                        //         $tags[$tag->id] = $tag->code . ' - ' . $tag->name;
                                        //     }
                                        // }

                                        // return $tags;

                                        return [];
                                    })
                                    ->required()
                                    ->visible(function ($get) {
                                        return $get('isNfeTomaCreditoIcms');
                                    })
                                    ->validationMessages([
                                        'required' => 'É obrigatório informar as etiquetas para credito de ICMS',
                                    ]),
                            ]),
                        Tabs\Tab::make('entry-settings')
                            ->label('Entradas')
                            ->schema([
                                Tabs::make('Tabs')
                                    ->tabs([
                                        Tabs\Tab::make('tax-incomes')
                                        ->label('Impostos')
                                            ->schema([
                                              // Livewire::make('organization.configuration.entrada-imposto-equivalente-form')
                                            ]),
                                        Tabs\Tab::make('Tab 2')
                                            ->schema([
                                                // ...
                                            ]),
                                        Tabs\Tab::make('Tab 3')
                                            ->schema([
                                                // ...
                                            ]),
                                    ])
                            ]),
                        Tabs\Tab::make('tags-default')
                            ->label('Etiquetas Padrao')
                            ->schema([
                                // ...
                            ]),
                    ]),


            ])
            ->statePath('data');
    }

    public function updateConfigurationOrganization()
    {

        $data = $this->form->getState();

        $service = app(OrganizationService::class);

        try {

            $service->update($this->organization, $data);
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erro ao atualizar dados da organização')
                ->body($e->getMessage())
                ->send();
            return;
        }

        Notification::make()
            ->title('Dados da organização atualizados')
            ->success()
            ->duration(3000)
            ->body('Os dados da organização foram atualizados com sucesso')
            ->send();
    }
    public function render()
    {
        return view('livewire.organization.configuration-organization-form');
    }
}
