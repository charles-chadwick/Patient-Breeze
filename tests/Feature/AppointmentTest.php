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

    expect($appointment->users()->count())->toBe(2);
    expect($appointment->primaryProvider()->is($doctor))->toBeTrue();
});

it('rejects attaching a patient as a provider', function (): void {
    $appointment = Appointment::factory()->create();

    $patient = User::factory()->create();
    $patient->assignRole(UserRole::Patient->value);

    $appointment->attachProvider($patient);
})->throws(DomainException::class);

it('creates an appointment with a single primary provider by default', function (): void {
    $appointment = Appointment::factory()->create();

    expect($appointment->users()->count())->toBe(1);

    $provider = $appointment->primaryProvider();

    expect($provider)->not->toBeNull();
    expect($provider->isPatient())->toBeFalse();
});

it('creates an appointment with multiple providers via withProviders state', function (): void {
    $appointment = Appointment::factory()->withProviders(2)->create();

    expect($appointment->users()->count())->toBe(2);
    expect($appointment->users->pluck('id')->unique()->count())->toBe(2);
    expect($appointment->primaryProvider())->not->toBeNull();
});

it('exposes appointments a user is attached to', function (): void {
    $doctor = User::factory()->create();
    $doctor->assignRole(UserRole::Doctor->value);

    $nurse = User::factory()->create();
    $nurse->assignRole(UserRole::Nurse->value);

    $attached = Appointment::factory()->withProvider($doctor)->create();
    $unrelated = Appointment::factory()->withProvider($nurse)->create();

    $appointment_ids = $doctor->appointments()->pluck('appointments.id');

    expect($appointment_ids)->toContain($attached->id);
    expect($appointment_ids)->not->toContain($unrelated->id);
});
