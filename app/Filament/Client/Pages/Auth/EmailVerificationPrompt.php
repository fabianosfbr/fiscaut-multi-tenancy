<?php

namespace App\Filament\Client\Pages\Auth;

use Filament\Pages\Auth\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected bool $hasTopbar = false;

    protected static string $view = 'filament.pages.auth.email-verification-prompt';
}
