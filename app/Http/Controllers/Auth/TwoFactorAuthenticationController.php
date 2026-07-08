<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ManagesTwoFactorAuthentication;

class TwoFactorAuthenticationController extends ManagesTwoFactorAuthentication
{
    protected function guardName(): string
    {
        return 'web';
    }

    protected function settingsComponent(): string
    {
        return 'Settings/Index';
    }

    protected function passwordConfirmationRoute(): string
    {
        return 'password.confirm';
    }
}
