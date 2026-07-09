<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;

it('returns the same permission names as spatie for a role-based user', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $expected = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
    $actual = $user->permissionNames()->sort()->values()->all();

    expect($actual)->toBe($expected)
        ->and($actual)->not->toBeEmpty();
});

it('includes permissions granted directly to the user', function (): void {
    $user = User::factory()->withRole(UserRole::Nurse)->create();

    Permission::findOrCreate('view_appointments');
    $user->givePermissionTo('view_appointments');

    $expected = $user->getAllPermissions()->pluck('name')->sort()->values()->all();

    expect($user->permissionNames()->sort()->values()->all())
        ->toBe($expected)
        ->toContain('view_appointments');
});

it('returns an empty collection for a user without roles or permissions', function (): void {
    $user = User::factory()->create();

    expect($user->permissionNames()->all())->toBe([]);
});

it('resolves permission names without hydrating permission models', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    $hydrated = 0;
    Event::listen('eloquent.retrieved: '.Permission::class, function () use (&$hydrated): void {
        $hydrated++;
    });

    $names = $user->permissionNames();

    expect($hydrated)->toBe(0)
        ->and($names)->not->toBeEmpty();
});
