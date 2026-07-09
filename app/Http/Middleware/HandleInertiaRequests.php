<?php

namespace App\Http\Middleware;

use App\Enums\SettingKey;
use App\Models\User;
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
            // Dark mode is a staff (web) feature; the patient portal always renders light.
            'theme' => fn () => $request->routeIs('portal.*')
                ? 'Light'
                : ($request->user('web')?->getSetting(SettingKey::Theme) ?? SettingKey::Theme->default()),
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
            'notifications' => fn () => $this->notificationsFor($request->user('web')),
        ];
    }

    /**
     * The authenticated staff user's recent notifications and unread count for
     * the top-bar bell. Empty for guests and portal patients.
     *
     * @return array{items: array<int, array<string, mixed>>, unread_count: int}
     */
    protected function notificationsFor(?User $user): array
    {
        if ($user === null) {
            return ['items' => [], 'unread_count' => 0];
        }

        $items = $user->notifications()->latest()->limit(10)->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'url' => route('notifications.open', $notification->id),
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ])
            ->all();

        return [
            'items' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }
}
