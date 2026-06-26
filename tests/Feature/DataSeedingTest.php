<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\PatientSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RoleAndPermissionSeeder::class);
    seed(UserSeeder::class);
});

it('imports exactly the CRM users with no synthetic accounts', function () {
    // 5 Super Admin + 11 Admin + 39 Staff in the CRM; no extra seeded admin.
    expect(User::count())->toBe(55)
        ->and(User::where('email', 'admin@example.com')->exists())->toBeFalse();
});

it('preserves CRM identities and timestamps and resets passwords to "password"', function () {
    $rick = User::where('email', 'slow.rick@example.com')->firstOrFail();

    expect($rick->first_name)->toBe('Slow')
        ->and($rick->last_name)->toBe('Rick')
        ->and($rick->created_at->toDateTimeString())->toBe('2021-07-29 11:03:21')
        ->and(Hash::check('password', $rick->password))->toBeTrue()
        ->and($rick->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
});

it('maps CRM roles onto application roles, randomising Admin into clinical roles', function () {
    $roleCount = fn (UserRole $role) => User::role($role->value)->count();

    // 5 CRM Super Admins; 39 Staff; 11 Admins spread across clinical roles.
    expect($roleCount(UserRole::SuperAdmin))->toBe(5)
        ->and($roleCount(UserRole::Staff))->toBe(39)
        ->and($roleCount(UserRole::Doctor) + $roleCount(UserRole::Nurse) + $roleCount(UserRole::MedicalAssistant))->toBe(11);
});

it('imports every CRM customer as a patient with generated medical data', function () {
    seed(PatientSeeder::class);

    expect(Patient::count())->toBe(519);

    $patient = Patient::where('email', 'slippery.stair9735@example.com')->firstOrFail();

    $patient->load('media');

    expect($patient->first_name)->toBe('Slippery')
        ->and($patient->last_name)->toBe('Stair')
        ->and($patient->created_at->toDateTimeString())->toBe('2025-08-26 20:19:52')
        ->and($patient->mrn)->toStartWith('MRN-')
        ->and($patient->gender_at_birth)->not->toBeNull()
        ->and($patient->date_of_birth)->not->toBeNull()
        ->and($patient->getFirstMedia('avatar'))->not->toBeNull()
        ->and(Hash::check('password', $patient->password))->toBeTrue();
});

it('attaches the imported avatar to each seeded user', function () {
    $user = User::where('email', 'slow.rick@example.com')->firstOrFail()->load('media');

    expect($user->getFirstMedia('avatar'))->not->toBeNull();
});

it('logs a creation activity attributed to an existing user for each patient', function () {
    seed(PatientSeeder::class);

    // The seeder backdates one creation activity per patient, attributed to a user.
    $causedCreations = Activity::query()
        ->where('subject_type', Patient::class)
        ->where('event', 'created')
        ->whereNotNull('causer_id')
        ->count();

    expect($causedCreations)->toBe(519);
});
