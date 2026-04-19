<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('renders the user show page', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $this->get(route('users.show', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Show')
            ->has('user')
        );
});

it('renders the users index page', function (): void {
    User::factory()->count(3)->create();

    $this->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users')
            ->has('search')
            ->has('sort_by')
            ->has('direction')
        );
});

it('renders the create user page', function (): void {
    $this->get(route('users.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Form')
            ->has('role_options')
            ->missing('user')
        );
});

it('creates a new user with the given password', function (): void {
    $this->post(route('users.store'), [
        'first_name' => 'Ned',
        'last_name' => 'Flanders',
        'email' => 'ned@springfield.com',
        'role' => UserRole::Doctor->value,
        'password' => 'okily-dokily1',
        'password_confirmation' => 'okily-dokily1',
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'ned@springfield.com')->first();

    expect($user)->not->toBeNull();
    expect($user->first_name)->toBe('Ned');
    expect($user->hasRole(UserRole::Doctor->value))->toBeTrue();
    expect(Hash::check('okily-dokily1', $user->password))->toBeTrue();
});

it('requires a password on store', function (): void {
    $this->post(route('users.store'), [
        'first_name' => 'Ned',
        'last_name' => 'Flanders',
        'email' => 'ned@springfield.com',
        'role' => UserRole::Doctor->value,
    ])->assertSessionHasErrors(['password']);
});

it('validates required fields on store', function (): void {
    $this->post(route('users.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'role', 'password']);
});

it('rejects a duplicate email on store', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('users.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
        'role' => UserRole::Staff->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors(['email']);
});

it('renders the edit user page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Nurse->value);

    $this->get(route('users.edit', $user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Users/Form')
            ->has('user')
            ->has('role_options')
        );
});

it('updates a user without changing the password when left blank', function (): void {
    $user = User::factory()->create(['password' => Hash::make('original-password')]);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Nurse->value,
    ])->assertRedirect(route('users.index'));

    expect(Hash::check('original-password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->hasRole(UserRole::Nurse->value))->toBeTrue();
});

it('updates the password when provided on edit', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => UserRole::Staff->value,
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertRedirect(route('users.index'));

    expect(Hash::check('new-password1', $user->fresh()->password))->toBeTrue();
});

it('allows the same email when updating the same user', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'same@example.com',
        'role' => UserRole::Staff->value,
    ])->assertRedirect(route('users.index'));
});

it('rejects an email belonging to another user on update', function (): void {
    User::factory()->create(['email' => 'other@example.com']);
    $user = User::factory()->create();
    $user->assignRole(UserRole::Staff->value);

    $this->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'other@example.com',
        'role' => UserRole::Staff->value,
    ])->assertSessionHasErrors(['email']);
});
