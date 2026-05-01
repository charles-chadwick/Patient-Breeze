<?php

use App\Enums\AppointmentRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $actor = User::factory()->withRole(UserRole::Staff)->create();
    User::factory()->withRole(UserRole::Staff)->create();
    User::factory()->withRole(UserRole::Staff)->create();

    $this->actingAs($actor);
});

it('attaches multiple non-patient users with distinct roles', function (): void {
    $appointment = Appointment::factory()->create();
    $appointment->users()->detach();

    $doctor = User::factory()->create();
    $doctor->assignRole(UserRole::Doctor->value);

    $nurse = User::factory()->create();
    $nurse->assignRole(UserRole::Nurse->value);

    $appointment->attachProvider($doctor, AppointmentRole::Primary);
    $appointment->attachProvider($nurse, AppointmentRole::Assistant);

    expect($appointment->users()->count())->toBe(2)
        ->and($appointment->primaryProvider()->is($doctor))->toBeTrue();
});

it('creates an appointment with a single primary provider by default', function (): void {
    $appointment = Appointment::factory()->create();

    expect($appointment->users()->count())->toBe(1)
        ->and($appointment->primaryProvider())->not->toBeNull();
});

it('creates an appointment with multiple providers via withProviders state', function (): void {
    $appointment = Appointment::factory()->withProviders(2)->create();

    expect($appointment->users()->count())->toBe(2)
        ->and($appointment->users->pluck('id')->unique()->count())->toBe(2)
        ->and($appointment->primaryProvider())->not->toBeNull();
});

it('exposes appointments a user is attached to', function (): void {
    $doctor = User::factory()->create();
    $doctor->assignRole(UserRole::Doctor->value);

    $nurse = User::factory()->create();
    $nurse->assignRole(UserRole::Nurse->value);

    $attached = Appointment::factory()->withProvider($doctor)->create();
    $unrelated = Appointment::factory()->withProvider($nurse)->create();

    $appointment_ids = $doctor->appointments()->pluck('appointments.id');

    expect($appointment_ids)->toContain($attached->id)
        ->and($appointment_ids)->not->toContain($unrelated->id);
});
