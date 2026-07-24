<?php

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it("lists today's appointments on the dashboard, ordered by start time", function (): void {
    // The appointment factory assigns providers from the existing staff pool.
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    Appointment::factory()->for($patient)->create([
        'date' => today(),
        'start_time' => '14:00:00',
        'end_time' => '14:30:00',
        'status' => AppointmentStatus::Confirmed,
    ]);
    Appointment::factory()->for($patient)->create([
        'date' => today(),
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($patient)->create([
        'date' => today()->addDay(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->actingAs($doctor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('can_view_appointments', true)
            ->has('todays_appointments', 2)
            ->where('todays_appointments.0.start_time', '09:00:00')
            ->where('todays_appointments.1.start_time', '14:00:00')
            ->where('todays_appointments.0.patient.id', $patient->id)
            ->has('todays_appointments.0.patient.avatar_url')
            ->has('todays_appointments.0.users')
        );
});

it('hides the appointment list from users who cannot view appointments', function (): void {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $user->roles->first()->revokePermissionTo('view_appointments');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Appointment::factory()->create(['date' => today()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_view_appointments', false)
            ->has('todays_appointments', 0)
        );
});
