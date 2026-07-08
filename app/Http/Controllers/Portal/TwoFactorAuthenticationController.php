<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ManagesTwoFactorAuthentication;

class TwoFactorAuthenticationController extends ManagesTwoFactorAuthentication
{
    protected function guardName(): string
    {
        return 'portal';
    }

    protected function settingsComponent(): string
    {
        return 'Portal/Settings';
    }

    protected function passwordConfirmationRoute(): string
    {
        return 'portal.password.confirm';
    }
}
