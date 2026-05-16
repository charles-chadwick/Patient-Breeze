<?php

namespace App\Http\Middleware\Portal;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, ['portal']);
    }

    protected function redirectTo(Request $request): ?string
    {
        return route('portal.login');
    }
}
