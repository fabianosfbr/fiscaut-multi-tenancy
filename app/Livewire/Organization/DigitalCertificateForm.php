<?php

namespace App\Livewire\Organization;

use Closure;
use Exception;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use App\Services\Tenant\OrganizationService;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;

class DigitalCertificateForm extends Component implements HasForms
{
    use InteractsWithForms;


    public ?array $data = [];

    public mixed $organization;

    public function mount(mixed $organization): void
    {
        $this->organization = $organization;
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
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
            ])
            ->statePath('data');
    }

    public function updateOrganizationCertificate()
    {

        $data = $this->form->getState();

        $service = app(OrganizationService::class);

        try {
            $data = $service->readerCertificateFile($data);

            $service->checkOwnerCertificate($this->organization, $data);

            $this->organization->digitalCertificate()->update([
                'validated_at' => $data['validated_at'],
                'password' => $data['password'],
                'content_file' => $data['content_file'],
            ]);

            $this->form->fill(['certificate' => null, 'password' => null, 'password_confirm' => null]);
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erro ao ler o certificado digital')
                ->body($e->getMessage())
                ->send();
            return;
        }

        Notification::make()
            ->title('Certificado Digital atualizado')
            ->success()
            ->duration(3000)
            ->body('O certificado digital foi atualizado com sucesso')
            ->send();
    }

    public function render()
    {
        return view('livewire.organization.digital-certificate-form');
    }
}
