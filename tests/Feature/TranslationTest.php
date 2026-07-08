<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('shares the active locale with the frontend', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('locale', app()->getLocale()));
});

it('resolves a translated, non-key label for every user role', function (): void {
    foreach (UserRole::cases() as $role) {
        $key = 'enums.user_role.'.$role->value;

        expect(Lang::has($key))->toBeTrue("Missing translation key: {$key}");
        expect($role->label())->toBe(__($key))->not->toBe($key);
    }
});

it('flashes a translated success message when a user is created', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());

    $this->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Doctor->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHas('success', __('flash.users.created'));
});

/**
 * Guard: every statically-referenced translation key in the migrated Users
 * Vue files must exist in lang/en so nothing silently renders as a raw key.
 *
 * @return array<int, string>
 */
function usersSliceTranslationKeys(): array
{
    $files = [
        resource_path('js/Pages/Users/Index.vue'),
        resource_path('js/Pages/Users/Show.vue'),
        resource_path('js/Pages/Users/Form.vue'),
        resource_path('js/Pages/Users/Partials/Form.vue'),
        resource_path('js/Components/UserCard.vue'),
    ];

    $keys = [];

    foreach ($files as $file) {
        $contents = file_get_contents($file);

        // Match $t('key') and trans('key') with a plain single-quoted literal.
        // Concatenated/dynamic keys (e.g. 'enums.user_role.' + name) are skipped
        // here and covered by the per-role test above.
        preg_match_all("/(?:\\\$t|trans)\('([a-z0-9_.]+)'\s*[),]/i", $contents, $matches);

        foreach ($matches[1] as $key) {
            $keys[$key] = $key;
        }
    }

    return array_values($keys);
}

it('defines every translation key referenced in the Users slice', function (): void {
    $keys = usersSliceTranslationKeys();

    expect($keys)->not->toBeEmpty();

    foreach ($keys as $key) {
        expect(Lang::has($key))->toBeTrue("Missing translation key: {$key}");
    }
});
