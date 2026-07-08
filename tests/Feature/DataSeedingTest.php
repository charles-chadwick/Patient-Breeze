<?php

use App\Enums\DoseForm;
use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\MedicationSeeder;
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

it('seeds the configured number of users with no synthetic accounts', function () {
    // 5 Super Admins + 11 clinical + 39 Staff.
    expect(User::count())->toBe(55)
        ->and(User::where('email', 'admin@example.com')->exists())->toBeFalse();
});

it('always seeds the same Super Admins', function () {
    $emails = [
        'slow.rick@example.com',
        'doofus.rick@example.com',
        'president.curtis@example.com',
        'frankensteins.monster@example.com',
        'reverse.giraffe@example.com',
    ];

    foreach ($emails as $email) {
        $super_admin = User::where('email', $email)->firstOrFail();

        expect($super_admin->hasRole(UserRole::SuperAdmin->value))->toBeTrue()
            ->and($super_admin->created_at->toDateTimeString())->toBe('2020-01-01 00:00:00')
            ->and(Hash::check('password', $super_admin->password))->toBeTrue();
    }

    expect(User::role(UserRole::SuperAdmin->value)->count())->toBe(5);
});

it('splits the remaining users across staff and clinical roles', function () {
    $roleCount = fn (UserRole $role) => User::role($role->value)->count();

    expect($roleCount(UserRole::Staff))->toBe(39)
        ->and($roleCount(UserRole::Doctor) + $roleCount(UserRole::Nurse) + $roleCount(UserRole::MedicalAssistant))->toBe(11);
});

it('attaches an avatar to each seeded user', function () {
    $super_admin = User::where('email', 'slow.rick@example.com')->firstOrFail()->load('media');

    expect($super_admin->getFirstMedia('avatar'))->not->toBeNull();
});

it('seeds the configured number of patients with generated medical data', function () {
    seed(PatientSeeder::class);

    expect(Patient::count())->toBe(519);

    $patient = Patient::query()->with('media')->first();

    expect($patient->mrn)->toStartWith('MRN-')
        ->and($patient->gender_at_birth)->not->toBeNull()
        ->and($patient->date_of_birth)->not->toBeNull()
        ->and($patient->getFirstMedia('avatar'))->not->toBeNull()
        ->and(Hash::check('password', $patient->password))->toBeTrue();
});

it('routes the middle words of multi-word characters into the middle_name column', function () {
    seed(PatientSeeder::class);

    // First and last names are always single words; every extra word becomes the middle name.
    expect(Patient::where('first_name', 'like', '% %')->exists())->toBeFalse()
        ->and(Patient::where('last_name', 'like', '% %')->exists())->toBeFalse()
        ->and(Patient::where('last_name', '')->exists())->toBeFalse()
        ->and(Patient::where('middle_name', '!=', '')->exists())->toBeTrue();
});

it('sanitizes every seeded email address', function () {
    seed(PatientSeeder::class);

    $emails = User::pluck('email')->merge(Patient::pluck('email'));

    foreach ($emails as $email) {
        expect($email)
            ->toBe(strtolower($email))
            ->not->toContain(' ')
            ->not->toContain("'")
            ->toEndWith('@example.com');
    }
});

it('seeds the medications catalog from the JSON dataset', function () {
    seed(MedicationSeeder::class);

    $expected = count(json_decode(file_get_contents(database_path('data/medications.json')), true));

    expect(Medication::count())->toBe($expected);
});

it('stores every seeded medication with a dose form from the enum', function () {
    seed(MedicationSeeder::class);

    $doseForms = Medication::query()->pluck('dose_form');

    expect($doseForms->every(fn (DoseForm $doseForm): bool => $doseForm instanceof DoseForm))->toBeTrue()
        ->and($doseForms->unique())->toHaveCount(count(DoseForm::cases()));
});

it('gives every seeded medication a unique ndc and required fields', function () {
    seed(MedicationSeeder::class);

    $medications = Medication::query()->get();

    expect($medications->pluck('ndc')->unique())->toHaveCount($medications->count());

    foreach ($medications as $medication) {
        expect($medication->type)->not->toBe('')
            ->and($medication->name)->not->toBe('')
            ->and($medication->dosage)->not->toBe('')
            ->and($medication->ndc)->toMatch('/^\d{5}-\d{4}-\d{2}$/');
    }
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
