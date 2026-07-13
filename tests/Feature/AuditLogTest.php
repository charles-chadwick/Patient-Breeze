<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('renders the audit log for a super admin', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
    Patient::factory()->create();

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AuditLog/Index')
            ->has('activities.data')
            ->has('causer_options')
            ->has('subject_options')
            ->has('event_options')
        );
});

it('renders the audit log for doctors and staff', function (UserRole $role): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('AuditLog/Index'));
})->with([
    'doctor' => [UserRole::Doctor],
    'staff' => [UserRole::Staff],
]);

it('forbids the audit log for roles without access', function (UserRole $role): void {
    $this->actingAs(User::factory()->withRole($role)->create());

    $this->get(route('audit-log.index'))->assertForbidden();
})->with([
    'nurse' => [UserRole::Nurse],
    'medical assistant' => [UserRole::MedicalAssistant],
]);

it('filters the audit log by event', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();      // logs a "created" event
    $patient->update(['first_name' => 'Renamed']); // logs an "updated" event

    $this->get(route('audit-log.index', ['event' => 'updated']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['event'] === 'updated'))
        );
});

it('filters the audit log by subject type', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());
    Patient::factory()->create();

    $this->get(route('audit-log.index', ['subject_type' => Patient::class]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['subject_type'] === Patient::class))
        );
});

it('scopes the audit log to a single patient and exposes their name', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::SuperAdmin)->create());

    $patient = Patient::factory()->create();
    $other = Patient::factory()->create();

    $this->get(route('audit-log.index', ['patient_id' => $patient->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('patient.id', $patient->id)
            ->where('patient.name', trim("{$patient->first_name} {$patient->last_name}"))
            ->where('filters.patient_id', $patient->id)
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['subject_id'] === $patient->id))
            ->where('activities.data', fn ($rows) => collect($rows)->doesntContain('subject_id', $other->id))
        );
});
