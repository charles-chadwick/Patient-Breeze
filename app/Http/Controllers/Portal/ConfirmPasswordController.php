<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ConfirmsPassword;

class ConfirmPasswordController extends ConfirmsPassword
{
    protected function guardName(): string
    {
        return 'portal';
    }

    protected function confirmComponent(): string
    {
        return 'Portal/ConfirmPassword';
    }

    protected function redirectRoute(): string
    {
        return 'portal.settings.index';
    }
}
