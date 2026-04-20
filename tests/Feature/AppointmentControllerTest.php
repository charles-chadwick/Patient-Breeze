<?php

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());
});

function makeStaffPayload(User $staff, string $role = 'Primary'): array
{
    return [
        'date' => '2026-06-01',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => AppointmentStatus::Scheduled->value,
        'reason' => 'Annual checkup',
        'notes' => null,
        'staff' => [
            ['user_id' => $staff->id, 'role' => $role],
        ],
    ];
}

it('renders the create form for an appointment', function () {
    $patient = Patient::factory()->create();

    $response = $this->get(route('patients.appointments.create', $patient));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Form')
            ->has('patient')
            ->has('status_options')
            ->has('role_options')
            ->has('staff_options')
        );
});

it('creates an appointment and redirects to patient show', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $response = $this->post(
        route('patients.appointments.store', $patient),
        makeStaffPayload($staff)
    );

    $response->assertRedirect(route('patients.show', $patient));
    expect(Appointment::count())->toBe(1);
    expect(Appointment::first()->users()->count())->toBe(1);
});

it('rejects store when a staff member has a conflicting appointment', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    // Staff already has 09:00–10:30 on the same date
    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $response = $this->post(
        route('patients.appointments.store', $patient),
        makeStaffPayload($staff)
    );

    $response->assertSessionHasErrors('staff');
    expect(Appointment::count())->toBe(1); // no new appointment created
});

it('renders the edit form for an appointment', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

    $response = $this->get(route('patients.appointments.edit', [$patient, $appointment]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Form')
            ->has('appointment')
            ->has('patient')
            ->has('status_options')
            ->has('role_options')
            ->has('staff_options')
        );
});

it('updates an appointment and redirects to patient show', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $payload = makeStaffPayload($staff);
    $payload['reason'] = 'Updated reason';

    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        $payload
    );

    $response->assertRedirect(route('patients.show', $patient));
    expect($appointment->fresh()->reason)->toBe('Updated reason');
});

it('excludes the appointment being edited from the conflict check', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    // Updating with the exact same time slot — should not conflict with itself
    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        makeStaffPayload($staff)
    );

    $response->assertRedirect(route('patients.show', $patient));
});

it('rejects update when a staff member conflicts with a different appointment', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    // Staff has a DIFFERENT appointment that overlaps with our update payload
    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    // The appointment we are editing (different time, no conflict on its own)
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-02', 'start_time' => '11:00', 'end_time' => '12:00']);

    // Trying to move it to 2026-06-01 10:00–11:00, which overlaps the first appointment
    $payload = makeStaffPayload($staff);

    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        $payload
    );

    $response->assertSessionHasErrors('staff');
});
