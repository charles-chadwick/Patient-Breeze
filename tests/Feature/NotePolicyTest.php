<?php

use App\Enums\UserRole;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('registers the four note permissions', function (): void {
    expect(UserRole::allPermissions())
        ->toContain('view_notes', 'create_notes', 'update_notes', 'delete_notes');
});

it('lets a doctor delete notes but forbids staff', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    expect($doctor->can('delete', $note))->toBeTrue()
        ->and($staff->can('delete', $note))->toBeFalse()
        ->and($staff->can('create', Note::class))->toBeTrue();
});
