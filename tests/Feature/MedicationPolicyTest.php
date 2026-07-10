<?php

use App\Enums\UserRole;
use App\Models\User;

it('generates the four medication permissions', function (): void {
    expect(UserRole::allPermissions())
        ->toContain('view_medications', 'create_medications', 'update_medications', 'delete_medications');
});

it('grants every staff role full medication catalog access', function (): void {
    foreach ([UserRole::SuperAdmin, UserRole::Doctor, UserRole::Nurse, UserRole::MedicalAssistant, UserRole::Staff] as $role) {
        expect($role->permissions())
            ->toContain('view_medications', 'create_medications', 'update_medications', 'delete_medications');
    }
});

it('lets a permitted user manage the catalog and forbids a role-less user', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $stranger = User::factory()->create();

    expect($doctor->can('create_medications'))->toBeTrue()
        ->and($stranger->can('create_medications'))->toBeFalse();
});
