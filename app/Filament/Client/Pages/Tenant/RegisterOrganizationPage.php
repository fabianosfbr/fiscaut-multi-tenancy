<?php

namespace App\Filament\Client\Pages\Tenant;

use Closure;
use Exception;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use App\Events\CreateOrganizationProcessed;
use App\Services\Tenant\OrganizationService;
use App\Enums\Tenant\RegimesEmpresariaisEnum;
use App\Enums\Tenant\AtividadesEmpresariaisEnum;

class RegisterOrganizationPage extends Page
{
    protected static ?string $slug = 'new-organization';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Adicionar nova empresa';

    public ?array $data = [];

    protected static string $view = 'filament.client.pages.tenant.register-organization-page';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...self::getDigitalCertificateForm(),
                // ...self::getOrganizationDataForm(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        // @phpstan-ignore-next-line
        $data = $this->form->getState();

        $organization = null;

        $service = app(OrganizationService::class);

        try {
            $data = $service->readerCertificateFile($data);

            $organization = $service->create($data);

        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erro ao criar a organização')
                ->body($e->getMessage())
                ->send();
            return;
        }


        $this->updateAuthUserData( $organization);


        Notification::make()
            ->success()
            ->title('Organização criada com sucesso')
            ->body('Agora é necessário completar os dados da empresa')
            ->send();

        redirect(route('filament.client.pages.edit-organization'));
    }

    public function returnAction(): Action
    {
        return Action::make('return')
            ->label('Cancelar')
            ->color('warning')
            ->url(route('filament.client.pages.dashboard')); // @phpstan-ignore-line
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Salvar')
            ->action(fn() => $this->create());
    }

    public static function getOrganizationDataForm()
    {
        return [
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
                        ->default('3525904')
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
        ];
    }

    public static function getDigitalCertificateForm()
    {
        return [
            Section::make('Certificado Digital')
                ->description('Insira o certificado digital e a senha da empresa que deseja cadastrar')
                ->schema([
                    FileUpload::make('certificate')
                        ->label('Certificado digital')
                        ->required()
                        ->preserveFilenames()
                        ->minSize(1)
                        ->maxSize(20)
                        ->rules([
                            fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                $extension = $value->getClientOriginalExtension();
                                if (!in_array($extension, ['pfx', 'p12'])) {
                                    $fail('Erro: arquivo inválido. O arquivo deve ser do tipo .pfx ou .p12' . $extension);
                                } else {
                                    Storage::put('certificates/' . $value->getClientOriginalName(), $value->get());
                                }
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(function (HasForms $livewire, FileUpload $component) {

                            $livewire->validateOnly($component->getStatePath());
                        })
                        ->columnSpan(2),
                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->required()
                        ->revealable()
                        ->same('password_confirm')
                        ->columnSpan(1),
                    TextInput::make('password_confirm')
                        ->label('Confirmar senha')
                        ->password()
                        ->revealable()
                        ->required()
                        ->columnSpan(1),
                ])->columns(2)

        ];
    }

    public function updateAuthUserData( $organization)
    {
        $roles = $organization->roles;

        $user = Auth()->user();
        $user->syncRoles($roles->pluck('name')->toArray());
        $user->last_organization_id = $organization->id;
        $user->saveQuietly();
    }
}
