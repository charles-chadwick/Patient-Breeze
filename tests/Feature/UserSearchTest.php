<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->currentUser = User::factory()->withRole(UserRole::Staff)->create([
        'first_name' => 'Current',
        'last_name' => 'User',
    ]);
});

it('searches users by name', function (): void {
    User::factory()->withRole(UserRole::Doctor)->create(['first_name' => 'Gregory', 'last_name' => 'House']);
    User::factory()->withRole(UserRole::Nurse)->create(['first_name' => 'Meredith', 'last_name' => 'Grey']);

    $this->actingAs($this->currentUser)
        ->getJson(route('users.search', ['search' => 'House']))
        ->assertOk()
        ->assertJsonCount(1, 'users')
        ->assertJsonPath('users.0.last_name', 'House')
        ->assertJsonStructure(['users' => [['id', 'first_name', 'last_name', 'avatar_url']]]);
});

it('excludes the authenticated user from results', function (): void {
    $this->actingAs($this->currentUser)
        ->getJson(route('users.search', ['search' => 'Current']))
        ->assertOk()
        ->assertJsonCount(0, 'users');
});

it('caps the number of results returned', function (): void {
    User::factory()->count(25)->withRole(UserRole::Staff)->create(['last_name' => 'Searchable']);

    $this->actingAs($this->currentUser)
        ->getJson(route('users.search', ['search' => 'Searchable']))
        ->assertOk()
        ->assertJsonCount(20, 'users');
});

it('requires authentication to search users', function (): void {
    $this->getJson(route('users.search', ['search' => 'House']))
        ->assertUnauthorized();
});
