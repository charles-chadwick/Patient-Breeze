<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => fn () => app()->getLocale(),
            'auth' => [
                'user' => fn () => $request->user('web')?->loadMissing('media')->only([
                    'id', 'first_name', 'last_name', 'email', 'prefix', 'suffix', 'avatar_url',
                ]),
                'portal_patient' => fn () => $request->user('portal')?->only([
                    'id', 'first_name', 'last_name',
                ]),
                'roles' => fn () => $request->user('web')?->getRoleNames() ?? [],
                'permissions' => fn () => $request->user('web')?->permissionNames() ?? [],
                'two_factor_enabled' => fn () => (bool) $request->user('web')?->hasEnabledTwoFactorAuthentication(),
                'portal_two_factor_enabled' => fn () => (bool) $request->user('portal')?->hasEnabledTwoFactorAuthentication(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
