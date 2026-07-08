<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ConfirmsPassword;

class ConfirmPasswordController extends ConfirmsPassword
{
    protected function guardName(): string
    {
        return 'web';
    }

    protected function confirmComponent(): string
    {
        return 'Auth/ConfirmPassword';
    }

    protected function redirectRoute(): string
    {
        return 'settings.index';
    }
}
