<?php

use App\Enums\GenderAtBirth;
use App\Enums\UserRole;
use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
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

    $patient = Patient::whereHas('user', fn ($q) => $q->where('email', 'homer@springfield.com'))->first();
    expect($patient->user->getFirstMedia('avatar'))->not->toBeNull();
});

it('uploads an avatar when updating a patient', function (): void {
    Storage::fake('public');

    $patient = Patient::factory()->create();

    $this->put(route('patients.update', $patient), [
        'first_name' => $patient->user->first_name,
        'last_name' => $patient->user->last_name,
        'email' => $patient->user->email,
        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
        'gender_at_birth' => $patient->gender_at_birth->value,
        'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
    ])->assertRedirect(route('patients.show', $patient));

    expect($patient->user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('removes an avatar when remove_avatar is true', function (): void {
    Storage::fake('public');

    $patient = Patient::factory()->create();
    $patient->user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    $this->put(route('patients.update', $patient), [
        'first_name' => $patient->user->first_name,
        'last_name' => $patient->user->last_name,
        'email' => $patient->user->email,
        'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
        'gender_at_birth' => $patient->gender_at_birth->value,
        'remove_avatar' => true,
    ])->assertRedirect(route('patients.show', $patient));

    expect($patient->user->fresh()->getFirstMedia('avatar'))->toBeNull();
});
