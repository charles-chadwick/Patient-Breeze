<?php

use App\Enums\GenderAtBirth;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());
});

it('uploads an avatar when creating a patient', function (): void {
    Storage::fake('public');

    $this->post(route('patients.store'), [
        'first_name' => 'Homer',
        'last_name' => 'Simpson',
        'email' => 'homer@springfield.com',
        'date_of_birth' => '1956-05-12',
        'gender_at_birth' => GenderAtBirth::Male->value,
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertRedirect();

    $patient = Patient::where('email', 'homer@springfield.com')->first();
    expect($patient->getFirstMedia('avatar'))->not->toBeNull();
});

it('uploads an avatar when updating a patient', function (): void {
    Storage::fake('public');

    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), [
        'first_name' => $patient->first_name,
        'last_name' => $patient->last_name,
        'email' => $patient->email,
        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
        'gender_at_birth' => $patient->gender_at_birth->value,
        'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
    ])->assertRedirect(route('patients.show', $patient));

    expect($patient->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('removes an avatar when remove_avatar is true', function (): void {
    Storage::fake('public');

    $patient = Patient::factory()->create();
    $patient->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    $this->put(route('patients.update', $patient), [
        'first_name' => $patient->first_name,
        'last_name' => $patient->last_name,
        'email' => $patient->email,
        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
        'gender_at_birth' => $patient->gender_at_birth->value,
        'remove_avatar' => true,
    ])->assertRedirect(route('patients.show', $patient));

    expect($patient->fresh()->getFirstMedia('avatar'))->toBeNull();
});

it('falls back to the local default avatar when no media is set', function (): void {
    $patient = Patient::factory()->create();

    expect($patient->avatar_url)->toBe(asset('storage/default-avatar.png'));
});

it('renders the patient show page with appointments and search props', function (): void {
    $patient = Patient::factory()->create();

    $this->get(route('patients.show', $patient))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('patient')
            ->has('appointments')
            ->has('appointment_search')
        );
});

it('filters appointments by reason on the patient show page', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()->withProvider($user)->create(['patient_id' => $patient->id, 'reason' => 'Annual checkup']);
    Appointment::factory()->withProvider($user)->create(['patient_id' => $patient->id, 'reason' => 'Follow-up visit']);

    $this->get(route('patients.show', [$patient, 'search' => 'checkup']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.reason', 'Annual checkup')
        );
});

it('paginates appointments on the patient show page', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    Appointment::factory()->withProvider($user)->count(12)->create(['patient_id' => $patient->id]);

    $this->get(route('patients.show', $patient))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('appointments.data', 10)
            ->where('appointments.total', 12)
        );
});
