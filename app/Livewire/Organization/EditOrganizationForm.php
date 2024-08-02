<?php

namespace App\Livewire\Organization;

use Exception;
use Livewire\Component;
use Filament\Forms\Form;
use App\Services\Tenant\OrganizationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Enums\Tenant\RegimesEmpresariaisEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Enums\Tenant\AtividadesEmpresariaisEnum;
use Filament\Forms\Concerns\InteractsWithForms;

class EditOrganizationForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public mixed $organization;

    public function mount(mixed $organization): void
    {
        $this->organization = $organization;
        $this->form->fill($organization->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Editar organização')
                ->description('Edite as informações da sua organização')
                ->schema([
                    TextInput::make('razao_social')
                        ->label('Razão Social')
                        ->disabled()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('cnpj')
                        ->label('CNPJ')
                        ->disabled()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('inscricao_estadual')
                        ->label('Inscrição Estadual')
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('inscricao_municipal')
                        ->label('Inscrição Municipal (sem dígito)')
                        ->columnSpan(2),

                    Select::make('cod_municipio_ibge')
                        ->label('Município')
                        ->required()
                        ->options([
                            '3525904' => 'Jundiaí',
                        ])
                        ->columnSpan(2),

                    Select::make('regime')
                        ->required()
                        ->options(RegimesEmpresariaisEnum::class)
                        ->columnSpan(2),
                    Select::make('atividade')
                        ->required()
                        ->multiple()
                        ->options(AtividadesEmpresariaisEnum::class)
                        ->columnSpan(2),


                ])->columns(6),

            Section::make('Serviços Habilitados')
                ->description('Informe os serviços que estarão habilitados para essa empresa')
                ->schema([
                    Toggle::make('is_enable_nfe_servico')
                        ->label('NFe')
                        ->inline(),
                    Toggle::make('is_enable_cte_servico')
                        ->label('CTe')
                        ->inline(),
                    Toggle::make('is_enable_nfse_servico')
                        ->label('NFSe')
                        ->inline(),
                    Toggle::make('is_enable_cfe_servico')
                        ->label('CFe')
                        ->inline(),
                    Toggle::make('is_enable_sync_sieg')
                        ->label('Sieg')
                        ->disabled()
                        ->inline(),

                ])->columns(5),
            ])
            ->statePath('data');
    }

    public function updateOrganization()
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
        return view('livewire.organization.edit-organization-form');
    }
}
