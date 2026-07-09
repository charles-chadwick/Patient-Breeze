<?php

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

it('renders the create patient page', function (): void {
    $this->get(route('patients.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Form')
            ->has('gender_at_birth_options')
            ->has('gender_identity_options')
        );
});

it('creates a new patient and redirects to show', function (): void {
    $this->post(route('patients.store'), [
        'prefix' => 'Dr.',
        'first_name' => 'John',
        'middle_name' => '',
        'last_name' => 'Doe',
        'suffix' => 'MD',
        'email' => 'john.doe@example.com',
        'date_of_birth' => '1985-06-15',
        'gender_at_birth' => GenderAtBirth::Male->value,
        'gender_identity' => GenderIdentity::Male->value,
        'blood_type' => 'O+',
    ])->assertRedirect();

    $patient = Patient::where('email', 'john.doe@example.com')->first();

    expect($patient)
        ->not->toBeNull()
        ->and($patient->mrn)->toStartWith('MRN-')
        ->and($patient->first_name)->toBe('John');
});

it('validates required fields on store', function (): void {
    $this->post(route('patients.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'date_of_birth', 'gender_at_birth']);
});

it('rejects a duplicate email on store', function (): void {
    Patient::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('patients.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
        'date_of_birth' => '1990-01-01',
        'gender_at_birth' => GenderAtBirth::Female->value,
    ])->assertSessionHasErrors(['email']);
});

it('renders the edit patient page', function (): void {
    $patient = Patient::factory()->create();

    $this->get(route('patients.edit', $patient))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Form')
            ->has('patient')
            ->has('gender_at_birth_options')
            ->has('gender_identity_options')
        );
});

it('updates a patient and redirects to show', function (): void {
    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), [
        'prefix' => 'Ms.',
        'first_name' => 'Updated',
        'middle_name' => '',
        'last_name' => 'Name',
        'suffix' => '',
        'email' => $patient->email,
        'date_of_birth' => '1992-03-20',
        'gender_at_birth' => GenderAtBirth::Female->value,
        'gender_identity' => null,
        'blood_type' => 'A+',
    ])->assertRedirect(route('patients.show', $patient));

    $fresh = $patient->fresh();
    expect($fresh->first_name)->toBe('Updated')
        ->and($fresh->blood_type)->toBe('A+');
});

it('allows the same email on update for the same patient', function (): void {
    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), [
        'first_name' => $patient->first_name,
        'middle_name' => '',
        'last_name' => $patient->last_name,
        'suffix' => '',
        'email' => $patient->email,
        'date_of_birth' => '1992-03-20',
        'gender_at_birth' => GenderAtBirth::Male->value,
        'blood_type' => null,
    ])->assertRedirect();
});

it('rejects a duplicate email on update', function (): void {
    Patient::factory()->create(['email' => 'other@example.com']);
    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'other@example.com',
        'date_of_birth' => '1990-01-01',
        'gender_at_birth' => GenderAtBirth::Male->value,
    ])->assertSessionHasErrors(['email']);
});

it('exposes note types on the patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->get(route('patients.show', $patient))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('note_types')
        );
});
