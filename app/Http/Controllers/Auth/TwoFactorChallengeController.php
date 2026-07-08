<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ChallengesTwoFactorAuthentication;

class TwoFactorChallengeController extends ChallengesTwoFactorAuthentication
{
    protected function guardName(): string
    {
        return 'web';
    }

    protected function challengeComponent(): string
    {
        return 'Auth/TwoFactorChallenge';
    }

    protected function loginRoute(): string
    {
        return 'login';
    }

    protected function intendedRoute(): string
    {
        return 'dashboard';
    }
}
