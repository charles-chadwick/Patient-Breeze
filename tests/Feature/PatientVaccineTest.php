<?php

use App\Enums\UserRole;
use App\Enums\VaccineRoute;
use App\Enums\VaccineSite;
use App\Enums\VaccineStatus;
use App\Models\Patient;
use App\Models\PatientVaccine;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

function vaccinePayload(array $overrides = []): array
{
    return array_merge([
        'vaccine' => 'Tdap',
        'cvx_code' => '115',
        'administered_on' => '2026-05-01',
        'dose_number' => 1,
        'status' => VaccineStatus::Completed->value,
        'route' => VaccineRoute::Intramuscular->value,
        'site' => VaccineSite::LeftDeltoid->value,
        'dose_amount' => '0.5 mL',
        'manufacturer' => 'Sanofi',
        'lot_number' => 'AB1234',
        'expires_on' => '2027-01-01',
        'notes' => 'Tolerated well.',
    ], $overrides);
}

it('records a vaccine on a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vaccines.store', $patient), vaccinePayload())
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $vaccine = PatientVaccine::firstOrFail();

    expect($vaccine->patient_id)->toBe($patient->id)
        ->and($vaccine->vaccine)->toBe('Tdap')
        ->and($vaccine->cvx_code)->toBe('115')
        ->and($vaccine->administered_on->toDateString())->toBe('2026-05-01')
        ->and($vaccine->dose_number)->toBe(1)
        ->and($vaccine->status)->toBe(VaccineStatus::Completed)
        ->and($vaccine->route)->toBe(VaccineRoute::Intramuscular)
        ->and($vaccine->site)->toBe(VaccineSite::LeftDeltoid)
        ->and($vaccine->dose_amount)->toBe('0.5 mL')
        ->and($vaccine->manufacturer)->toBe('Sanofi')
        ->and($vaccine->lot_number)->toBe('AB1234')
        ->and($vaccine->expires_on->toDateString())->toBe('2027-01-01');
});

it('attributes the dose to the recording user by default', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vaccines.store', $patient), vaccinePayload());

    expect(PatientVaccine::firstOrFail()->administered_by)->toBe($this->staff->id);
});

it('attributes the dose to another clinician when one is named', function (): void {
    $patient = Patient::factory()->create();
    $nurse = User::factory()->withRole(UserRole::Nurse)->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vaccines.store', $patient), vaccinePayload([
            'administered_by' => $nurse->id,
        ]));

    expect(PatientVaccine::firstOrFail()->administered_by)->toBe($nurse->id);
});

it('validates the vaccine payload', function (array $overrides, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vaccines.store', $patient), vaccinePayload($overrides))
        ->assertSessionHasErrors($invalidField);

    expect(PatientVaccine::count())->toBe(0);
})->with([
    'missing vaccine' => [['vaccine' => ''], 'vaccine'],
    'missing date' => [['administered_on' => ''], 'administered_on'],
    'invalid date' => [['administered_on' => 'not-a-date'], 'administered_on'],
    'missing status' => [['status' => ''], 'status'],
    'invalid status' => [['status' => 'Nope'], 'status'],
    'invalid route' => [['route' => 'Nope'], 'route'],
    'invalid site' => [['site' => 'Nope'], 'site'],
    'zero dose number' => [['dose_number' => 0], 'dose_number'],
    'non-numeric dose number' => [['dose_number' => 'first'], 'dose_number'],
    'invalid expiry date' => [['expires_on' => 'not-a-date'], 'expires_on'],
    'unknown administrator' => [['administered_by' => 999999], 'administered_by'],
]);

it('records a vaccine with only the clinical core fields', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vaccines.store', $patient), [
            'vaccine' => 'Influenza, Seasonal, Injectable',
            'administered_on' => '2026-05-01',
            'status' => VaccineStatus::Refused->value,
        ])
        ->assertSessionHasNoErrors();

    $vaccine = PatientVaccine::firstOrFail();

    expect($vaccine->status)->toBe(VaccineStatus::Refused)
        ->and($vaccine->route)->toBeNull()
        ->and($vaccine->site)->toBeNull()
        ->and($vaccine->lot_number)->toBeNull()
        ->and($vaccine->dose_number)->toBeNull();
});

it('removes a vaccine record from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $vaccine = PatientVaccine::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.vaccines.destroy', [$patient, $vaccine]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientVaccine::find($vaccine->id))->toBeNull()
        ->and(PatientVaccine::withTrashed()->find($vaccine->id))->not->toBeNull();
});

it('scopes the vaccine to its patient in the route binding', function (): void {
    $patient_a = Patient::factory()->create();
    $patient_b = Patient::factory()->create();
    $vaccine = PatientVaccine::factory()->create(['patient_id' => $patient_a->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.vaccines.destroy', [$patient_b, $vaccine]))
        ->assertNotFound();

    expect(PatientVaccine::find($vaccine->id))->not->toBeNull();
});

it('includes the vaccines list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'vaccine' => 'Tdap',
        'cvx_code' => '115',
        'status' => VaccineStatus::Completed,
        'route' => VaccineRoute::Intramuscular,
        'site' => VaccineSite::LeftDeltoid,
        'administered_by' => $this->staff->id,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('patient_vaccines', 1)
            ->has('vaccine_status_options')
            ->has('vaccine_route_options')
            ->has('vaccine_site_options')
            ->where('patient_vaccines.0.vaccine', 'Tdap')
            ->where('patient_vaccines.0.cvx_code', '115')
            ->where('patient_vaccines.0.is_administered', true)
            ->where('patient_vaccines.0.route_label', VaccineRoute::Intramuscular->label())
            ->where('patient_vaccines.0.site_label', VaccineSite::LeftDeltoid->label())
            ->where('patient_vaccines.0.administered_by.id', $this->staff->id)
        );
});

it('orders the chart list with the most recent dose first', function (): void {
    $patient = Patient::factory()->create();

    PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'vaccine' => 'Older Dose',
        'administered_on' => '2024-01-01',
    ]);
    PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'vaccine' => 'Newer Dose',
        'administered_on' => '2026-01-01',
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('patient_vaccines.0.vaccine', 'Newer Dose')
            ->where('patient_vaccines.1.vaccine', 'Older Dose')
        );
});

it('flags a dose given after its lot had expired', function (): void {
    $patient = Patient::factory()->create();

    PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'administered_on' => '2026-05-01',
        'expires_on' => '2026-04-01',
        'status' => VaccineStatus::Completed,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('patient_vaccines.0.was_expired_when_administered', true)
        );
});

it('does not flag an in-date lot or a dose that was never given', function (): void {
    $patient = Patient::factory()->create();

    $in_date = PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'administered_on' => '2026-05-01',
        'expires_on' => '2026-06-01',
        'status' => VaccineStatus::Completed,
    ]);

    $refused = PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'administered_on' => '2026-05-01',
        'expires_on' => '2026-04-01',
        'status' => VaccineStatus::Refused,
    ]);

    expect($in_date->wasExpiredWhenAdministered())->toBeFalse()
        ->and($refused->wasExpiredWhenAdministered())->toBeFalse();
});

it('scopes the administered history to completed doses', function (): void {
    $patient = Patient::factory()->create();

    PatientVaccine::factory()->create(['patient_id' => $patient->id, 'status' => VaccineStatus::Completed]);
    PatientVaccine::factory()->create(['patient_id' => $patient->id, 'status' => VaccineStatus::Refused]);
    PatientVaccine::factory()->create(['patient_id' => $patient->id, 'status' => VaccineStatus::EnteredInError]);

    expect($patient->patientVaccines()->administered()->count())->toBe(1)
        ->and($patient->patientVaccines()->count())->toBe(3);
});

it('requires authentication to record a vaccine', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('patients.vaccines.store', $patient), vaccinePayload())
        ->assertRedirect(route('login'));

    expect(PatientVaccine::count())->toBe(0);
});
