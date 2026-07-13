<?php

use App\Enums\DiagnosisStatus;
use App\Enums\UserRole;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

it('adds a diagnosis to a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.diagnoses.store', $patient), [
            'diagnosis' => 'Essential (primary) hypertension',
            'icd10_code' => 'I10',
            'diagnosed_on' => '2026-05-01',
            'status' => DiagnosisStatus::Active->value,
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $diagnosis = PatientDiagnosis::firstOrFail();

    expect($diagnosis->patient_id)->toBe($patient->id)
        ->and($diagnosis->diagnosis)->toBe('Essential (primary) hypertension')
        ->and($diagnosis->icd10_code)->toBe('I10')
        ->and($diagnosis->diagnosed_on->toDateString())->toBe('2026-05-01')
        ->and($diagnosis->status)->toBe(DiagnosisStatus::Active);
});

it('validates the diagnosis payload', function (array $overrides, string $invalidField): void {
    $patient = Patient::factory()->create();

    $payload = array_merge([
        'diagnosis' => 'Essential (primary) hypertension',
        'icd10_code' => 'I10',
        'diagnosed_on' => '2026-05-01',
        'status' => DiagnosisStatus::Active->value,
    ], $overrides);

    $this->actingAs($this->staff)
        ->post(route('patients.diagnoses.store', $patient), $payload)
        ->assertSessionHasErrors($invalidField);

    expect(PatientDiagnosis::count())->toBe(0);
})->with([
    'missing diagnosis' => [['diagnosis' => ''], 'diagnosis'],
    'missing icd10 code' => [['icd10_code' => ''], 'icd10_code'],
    'missing diagnosed on' => [['diagnosed_on' => ''], 'diagnosed_on'],
    'invalid diagnosed on' => [['diagnosed_on' => 'not-a-date'], 'diagnosed_on'],
    'missing status' => [['status' => ''], 'status'],
    'invalid status' => [['status' => 'Nope'], 'status'],
]);

it('removes a diagnosis from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $diagnosis = PatientDiagnosis::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.diagnoses.destroy', [$patient, $diagnosis]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientDiagnosis::find($diagnosis->id))->toBeNull()
        ->and(PatientDiagnosis::withTrashed()->find($diagnosis->id))->not->toBeNull();
});

it('scopes the diagnosis to its patient in the route binding', function (): void {
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $diagnosis = PatientDiagnosis::factory()->create(['patient_id' => $patientA->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.diagnoses.destroy', [$patientB, $diagnosis]))
        ->assertNotFound();

    expect(PatientDiagnosis::find($diagnosis->id))->not->toBeNull();
});

it('includes the diagnoses list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientDiagnosis::factory()->create([
        'patient_id' => $patient->id,
        'diagnosis' => 'Type 2 diabetes mellitus without complications',
        'icd10_code' => 'E11.9',
        'diagnosed_on' => '2026-05-01',
        'status' => DiagnosisStatus::Chronic,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('patient_diagnoses', 1)
            ->has('diagnosis_status_options')
            ->where('patient_diagnoses.0.diagnosis', 'Type 2 diabetes mellitus without complications')
            ->where('patient_diagnoses.0.icd10_code', 'E11.9')
            ->where('patient_diagnoses.0.diagnosed_on', '2026-05-01')
            ->where('patient_diagnoses.0.status', DiagnosisStatus::Chronic->value)
            ->where('patient_diagnoses.0.status_label', DiagnosisStatus::Chronic->label())
        );
});

it('searches the diagnosis catalog by name', function (): void {
    Diagnosis::factory()->create(['diagnosis' => 'Essential (primary) hypertension', 'icd10_code' => 'I10']);
    Diagnosis::factory()->create(['diagnosis' => 'Type 2 diabetes mellitus', 'icd10_code' => 'E11.9']);

    $this->actingAs($this->staff)
        ->getJson(route('diagnoses.search', ['search' => 'hypertension']))
        ->assertOk()
        ->assertJsonCount(1, 'diagnoses')
        ->assertJsonPath('diagnoses.0.icd10_code', 'I10');
});

it('searches the diagnosis catalog by icd10 code', function (): void {
    Diagnosis::factory()->create(['diagnosis' => 'Essential (primary) hypertension', 'icd10_code' => 'I10']);
    Diagnosis::factory()->create(['diagnosis' => 'Type 2 diabetes mellitus', 'icd10_code' => 'E11.9']);

    $this->actingAs($this->staff)
        ->getJson(route('diagnoses.search', ['search' => 'E11']))
        ->assertOk()
        ->assertJsonCount(1, 'diagnoses')
        ->assertJsonPath('diagnoses.0.diagnosis', 'Type 2 diabetes mellitus');
});

it('requires authentication to search diagnoses', function (): void {
    $this->getJson(route('diagnoses.search', ['search' => 'hypertension']))
        ->assertUnauthorized();
});
