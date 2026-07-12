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

it('forbids the audit log for non super admins', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $this->get(route('audit-log.index'))->assertForbidden();
});

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
