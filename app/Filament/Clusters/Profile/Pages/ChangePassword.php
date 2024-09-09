<?php

namespace App\Filament\Clusters\Profile\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Filament\Clusters\Profile;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Password;
use Filament\Pages\Concerns\InteractsWithFormActions;

class ChangePassword extends BaseProfile
{
    use InteractsWithFormActions;
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Alterar senha';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'me/change-password';

    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.clusters.profile.pages.change-password';

    protected static ?string $cluster = Profile::class;

    public function mount(): void
    {
        $data = $this->getUser()->attributesToArray();

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Alterar senha')
                    ->description('Alterar senha do usuário')
                    ->schema([
                        TextInput::make('password')
                            ->hint(new HtmlString('<span class="text-red-500">A senha deve ter pelo menos 8 caracteres, incluindo pelo menos uma letra maiúscula, um número e um caractere especial.</span>'))
                            ->label('Senha')
                            ->required()
                            ->revealable()
                            ->password()
                            ->rule(Password::min(8)->mixedCase()->letters()->numbers()->symbols())
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->autofocus()
                            ->extraInputAttributes(['data-cy' => 'input-profile-change-password']),
                        TextInput::make('passwordConfirmation')
                            ->label('Confirmar senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false)
                            ->extraInputAttributes(['data-cy' => 'input-profile-change-password-confirmation']),
                    ]),

            ])
            ->model($this->getUser())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->handleRecordUpdate($this->getUser(), $data);

        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_'.Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;

        $this->getSavedNotification('Informações atualizadas com sucesso')->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Salvar')
            ->submit('save')
            ->keyBindings(['mod+s'])
            ->extraAttributes(['data-cy' => 'save-change-password-button']);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('back')
            ->label('Página Inicial')
            ->url('/app')
            ->extraAttributes(['data-cy' => 'cancel-change-password-button']);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
