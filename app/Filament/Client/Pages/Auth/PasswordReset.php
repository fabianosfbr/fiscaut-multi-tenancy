<?php

namespace App\Filament\Client\Pages\Auth;

use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\PasswordReset\ResetPassword;
use Illuminate\Validation\Rules\Password;

/**
 * @codeCoverageIgnore
 */
class PasswordReset extends ResetPassword
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.email.label'))
                    ->disabled()
                    ->autofocus(),
                $this->getPasswordFormComponent()
                    ->rules([
                        Password::default()
                            ->mixedCase()
                            ->symbols()
                            ->numbers(),
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (password_verify($value, User::where('email', $this->email)->first()->password)) {
                                $fail('Você não pode usar a mesma senha para redefinição de senha');
                            }
                        },
                    ]
                    )
                    ->validationMessages([
                        'required' => 'A senha é obrigatória',
                        'min' => 'A senha deve ter pelo menos 8 caracteres',
                        'same' => 'A senha deve ser igual à confirmação de senha',
                        'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas',
                        'password.symbols' => 'A senha deve conter símbolos',
                        'password.numbers' => 'A senha deve conter números',
                        'password.letters' => 'A senha deve conter letras',
                    ]),
                TextInput::make('passwordConfirmation')
                    ->label(__('filament-panels::pages/auth/password-reset/reset-password.form.password_confirmation.label'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->dehydrated(false),

            ]);
    }
}
