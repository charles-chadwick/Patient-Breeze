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
            ->missing('staff_options')
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
    expect(Appointment::count())->toBe(1)
        ->and(Appointment::first()->users()->count())->toBe(1);
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
            ->missing('staff_options')
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

it('soft-deletes an appointment and redirects to patient show', function () {
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

    $response = $this->delete(route('patients.appointments.destroy', [$patient, $appointment]));

    $response->assertRedirect(route('patients.show', $patient));
    $this->assertSoftDeleted($appointment);
});

it('forbids deleting an appointment without the delete permission', function () {
    // The default acting user is Staff, whose role lacks delete_appointments.
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

    $response = $this->delete(route('patients.appointments.destroy', [$patient, $appointment]));

    $response->assertForbidden();
    expect($appointment->fresh()->trashed())->toBeFalse();
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

// ── Appointments index ────────────────────────────────────────────────────────

it('renders the appointments index page', function (): void {
    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Index')
            ->has('appointments')
            ->has('date')
            ->has('view')
            ->has('search')
            ->has('staff')
            ->has('selected_staff')
            ->missing('staff_options')
        );
});

it('resolves only the applied staff for the calendar filter badges', function (): void {
    $applied = User::factory()->withRole(UserRole::Doctor)->create();
    User::factory()->withRole(UserRole::Nurse)->create();

    $this->get(route('appointments.index', ['staff' => [$applied->id]]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Index')
            ->where('staff', [$applied->id])
            ->has('selected_staff', 1)
            ->where('selected_staff.0.id', $applied->id)
        );
});

it('defaults to week view and today when no params given', function (): void {
    $this->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'week')
            ->where('date', now()->toDateString())
        );
});

it('falls back to week view for an unrecognised view param', function (): void {
    $this->get(route('appointments.index', ['view' => 'month']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('view', 'week'));
});

it('ignores a whitespace-only search and returns all appointments in range', function (): void {
    $patient = Patient::factory()->create();
    Appointment::factory()->forDate('2026-06-10')->create(['patient_id' => $patient->id]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day', 'search' => '   ']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('appointments', 1));
});

it('returns only appointments for the requested date in day view', function (): void {
    $patient = Patient::factory()->create();
    Appointment::factory()->forDate('2026-06-10')->create(['patient_id' => $patient->id]);
    Appointment::factory()->forDate('2026-06-11')->create(['patient_id' => $patient->id]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('appointments', 1));
});

it('returns appointments for the full Mon–Sun week in week view', function (): void {
    $patient = Patient::factory()->create();
    // 2026-06-08 is Monday, 2026-06-14 is Sunday
    Appointment::factory()->forDate('2026-06-08')->create(['patient_id' => $patient->id]);
    Appointment::factory()->forDate('2026-06-14')->create(['patient_id' => $patient->id]);
    Appointment::factory()->forDate('2026-06-15')->create(['patient_id' => $patient->id]); // outside

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'week']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('appointments', 2));
});

it('filters appointments by patient name search', function (): void {
    $matching = Patient::factory()->create(['first_name' => 'Zebediah', 'last_name' => 'Quincey']);
    $other = Patient::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
    Appointment::factory()->forDate('2026-06-10')->create(['patient_id' => $matching->id]);
    Appointment::factory()->forDate('2026-06-10')->create(['patient_id' => $other->id]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day', 'search' => 'Zebediah']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('appointments', 1));
});

it('filters appointments by staff ids', function (): void {
    $staffA = User::factory()->withRole(UserRole::Staff)->create();
    $staffB = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();
    Appointment::factory()->forDate('2026-06-10')->withProvider($staffA)->create(['patient_id' => $patient->id]);
    Appointment::factory()->forDate('2026-06-10')->withProvider($staffB)->create(['patient_id' => $patient->id]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day', 'staff' => [$staffA->id]]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('appointments', 1));
});

it('includes assigned users with their roles for the popover', function (): void {
    $provider = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();
    Appointment::factory()->forDate('2026-06-10')->withProvider($provider)->create(['patient_id' => $patient->id]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('appointments.0.users.0.id', $provider->id)
            ->where('appointments.0.users.0.roles.0.name', UserRole::Doctor->value)
            ->etc()
        );
});

it('orders appointments by start_time within a day', function (): void {
    $patient = Patient::factory()->create();
    Appointment::factory()->forDate('2026-06-10')->create([
        'patient_id' => $patient->id,
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
    ]);
    Appointment::factory()->forDate('2026-06-10')->create([
        'patient_id' => $patient->id,
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->get(route('appointments.index', ['date' => '2026-06-10', 'view' => 'day']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('appointments.0.start_time', '09:00:00')
            ->where('appointments.1.start_time', '14:00:00')
        );
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

it('searches assignable staff by name', function () {
    User::factory()->withRole(UserRole::Doctor)->create(['first_name' => 'Gregory', 'last_name' => 'House']);
    User::factory()->withRole(UserRole::Nurse)->create(['first_name' => 'Meredith', 'last_name' => 'Grey']);

    $this->getJson(route('appointments.staff.search', ['search' => 'House']))
        ->assertOk()
        ->assertJsonCount(1, 'staff')
        ->assertJsonPath('staff.0.last_name', 'House')
        ->assertJsonStructure(['staff' => [['id', 'first_name', 'last_name', 'avatar_url']]]);
});

it('excludes super admins from staff search', function () {
    User::factory()->withRole(UserRole::SuperAdmin)->create(['first_name' => 'Super', 'last_name' => 'Admin']);

    $this->getJson(route('appointments.staff.search', ['search' => 'Admin']))
        ->assertOk()
        ->assertJsonCount(0, 'staff');
});

it('caps the number of staff results returned', function () {
    User::factory()->count(25)->withRole(UserRole::Staff)->create(['last_name' => 'Assignable']);

    $this->getJson(route('appointments.staff.search', ['search' => 'Assignable']))
        ->assertOk()
        ->assertJsonCount(20, 'staff');
});

it('exposes appointment form options on the patient chart', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('status_options')
            ->has('role_options')
        );
});
