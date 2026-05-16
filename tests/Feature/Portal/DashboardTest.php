<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('redirects unauthenticated patients to the portal login', function (): void {
    $this->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));
});

it('renders the dashboard for an authenticated patient', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Dashboard')
            ->has('patient')
            ->has('appointments')
            ->has('discussions')
            ->has('documents')
        );
});

it('does not expose another patient\'s appointments', function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    User::factory()->withRole(UserRole::Staff)->create();

    $patientA = Patient::factory()->create(['password' => bcrypt('password')]);
    $patientB = Patient::factory()->create(['password' => bcrypt('password')]);

    Appointment::factory()->forDate(now()->addDay()->toDateString())->create(['patient_id' => $patientA->id]);

    $this->actingAs($patientB, 'portal')
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Dashboard')
            ->where('appointments', [])
        );
});
