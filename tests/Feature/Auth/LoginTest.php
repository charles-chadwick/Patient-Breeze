<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('renders the login page', function (): void {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('redirects authenticated users away from login', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('logs in a staff user and redirects to dashboard', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function (): void {
    User::factory()->withRole(UserRole::Staff)->create(['email' => 'test@example.com']);

    $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});


it('logs out an authenticated user', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('redirects unauthenticated users to login', function (): void {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
