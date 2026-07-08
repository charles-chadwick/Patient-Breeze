<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ChallengesTwoFactorAuthentication;

class TwoFactorChallengeController extends ChallengesTwoFactorAuthentication
{
    protected function guardName(): string
    {
        return 'portal';
    }

    protected function challengeComponent(): string
    {
        return 'Portal/TwoFactorChallenge';
    }

    protected function loginRoute(): string
    {
        return 'portal.login';
    }

    protected function intendedRoute(): string
    {
        return 'portal.dashboard';
    }
}
