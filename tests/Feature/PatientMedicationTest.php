<?php

use App\Enums\DoseForm;
use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\PatientMedication;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

it('adds a medication to a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.medications.store', $patient), [
            'type' => 'Statin',
            'name' => 'Atorvastatin',
            'dosage' => '20 mg',
            'dose_form' => DoseForm::Tablet->value,
            'ndc' => '12345-6789-01',
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $medication = PatientMedication::firstOrFail();

    expect($medication->patient_id)->toBe($patient->id)
        ->and($medication->name)->toBe('Atorvastatin')
        ->and($medication->dose_form)->toBe(DoseForm::Tablet);
});

it('validates the medication payload', function (array $payload, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.medications.store', $patient), $payload)
        ->assertSessionHasErrors($invalidField);

    expect(PatientMedication::count())->toBe(0);
})->with([
    'missing name' => [['type' => 'Statin', 'dosage' => '20 mg', 'dose_form' => 'Tablet'], 'name'],
    'missing dosage' => [['type' => 'Statin', 'name' => 'Atorvastatin', 'dose_form' => 'Tablet'], 'dosage'],
    'missing dose form' => [['type' => 'Statin', 'name' => 'Atorvastatin', 'dosage' => '20 mg'], 'dose_form'],
    'invalid dose form' => [['type' => 'Statin', 'name' => 'Atorvastatin', 'dosage' => '20 mg', 'dose_form' => 'Nope'], 'dose_form'],
]);

it('removes a medication from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $medication = PatientMedication::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.medications.destroy', [$patient, $medication]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientMedication::find($medication->id))->toBeNull()
        ->and(PatientMedication::withTrashed()->find($medication->id))->not->toBeNull();
});

it('scopes the medication to its patient in the route binding', function (): void {
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $medication = PatientMedication::factory()->create(['patient_id' => $patientA->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.medications.destroy', [$patientB, $medication]))
        ->assertNotFound();

    expect(PatientMedication::find($medication->id))->not->toBeNull();
});

it('includes the medications list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientMedication::factory()->create([
        'patient_id' => $patient->id,
        'dose_form' => DoseForm::Capsule,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('medications', 1)
            ->has('dose_form_options')
            ->where('medications.0.dose_form_label', DoseForm::Capsule->label())
        );
});

it('searches the medication catalog by name', function (): void {
    Medication::factory()->create(['name' => 'Amoxicillin', 'type' => 'Antibiotic']);
    Medication::factory()->create(['name' => 'Lisinopril', 'type' => 'ACE Inhibitor']);

    $this->actingAs($this->staff)
        ->getJson(route('medications.search', ['search' => 'Amox']))
        ->assertOk()
        ->assertJsonCount(1, 'medications')
        ->assertJsonPath('medications.0.name', 'Amoxicillin');
});

it('searches the medication catalog by type', function (): void {
    Medication::factory()->create(['name' => 'Amoxicillin', 'type' => 'Antibiotic']);
    Medication::factory()->create(['name' => 'Lisinopril', 'type' => 'ACE Inhibitor']);

    $this->actingAs($this->staff)
        ->getJson(route('medications.search', ['search' => 'Antibiotic']))
        ->assertOk()
        ->assertJsonCount(1, 'medications')
        ->assertJsonPath('medications.0.name', 'Amoxicillin');
});

it('requires authentication to search medications', function (): void {
    $this->getJson(route('medications.search', ['search' => 'Amox']))
        ->assertUnauthorized();
});
