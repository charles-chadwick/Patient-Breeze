<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

/*
 * Guards the controller -> Vue prop-name contract for the appointment search
 * box on the show pages. Inertia does NOT convert snake_case props to camelCase,
 * so a `appointmentSearch` prop would silently never receive the controller's
 * `appointment_search` value. These pages must consume the snake_case name.
 */

it('consumes the snake_case appointment_search prop on show pages', function (string $file): void {
    $contents = file_get_contents(resource_path($file));

    expect($contents)
        ->toContain('appointment_search:')
        ->not->toContain('appointmentSearch');
})->with([
    'js/Pages/Users/Show.vue',
    'js/Pages/Patients/Show.vue',
]);

it('shares the authenticated user avatar_url so the user menu can render it', function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $user = User::factory()->withRole(UserRole::SuperAdmin)->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('auth.user.avatar_url'));
});

it('passes appointment_search to the users show page reflecting the query', function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->get(route('users.show', ['user' => $user, 'search' => 'cardiology']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('appointment_search', 'cardiology'));
});
