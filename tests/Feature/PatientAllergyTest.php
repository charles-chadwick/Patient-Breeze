<?php

use App\Enums\AllergenCategory;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AllergyStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

function allergyPayload(array $overrides = []): array
{
    return array_merge([
        'allergen' => 'Penicillin',
        'category' => AllergenCategory::Drug->value,
        'reactions' => [AllergyReaction::Hives->value, AllergyReaction::Swelling->value],
        'severity' => AllergySeverity::Severe->value,
        'status' => AllergyStatus::Active->value,
        'onset_on' => '2026-05-01',
        'notes' => 'Reported by patient.',
    ], $overrides);
}

it('adds an allergy to a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.allergies.store', $patient), allergyPayload())
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $allergy = PatientAllergy::firstOrFail();

    expect($allergy->patient_id)->toBe($patient->id)
        ->and($allergy->allergen)->toBe('Penicillin')
        ->and($allergy->category)->toBe(AllergenCategory::Drug)
        ->and($allergy->reactions)->toBe([AllergyReaction::Hives->value, AllergyReaction::Swelling->value])
        ->and($allergy->severity)->toBe(AllergySeverity::Severe)
        ->and($allergy->status)->toBe(AllergyStatus::Active)
        ->and($allergy->onset_on->toDateString())->toBe('2026-05-01');
});

it('stamps the allergy list as reviewed when an allergy is recorded', function (): void {
    $patient = Patient::factory()->create();

    expect($patient->allergies_reviewed_at)->toBeNull();

    $this->actingAs($this->staff)
        ->post(route('patients.allergies.store', $patient), allergyPayload());

    $patient->refresh();

    expect($patient->allergies_reviewed_at)->not->toBeNull()
        ->and($patient->allergies_reviewed_by)->toBe($this->staff->id);
});

it('validates the allergy payload', function (array $overrides, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.allergies.store', $patient), allergyPayload($overrides))
        ->assertSessionHasErrors($invalidField);

    expect(PatientAllergy::count())->toBe(0);
})->with([
    'missing allergen' => [['allergen' => ''], 'allergen'],
    'missing category' => [['category' => ''], 'category'],
    'invalid category' => [['category' => 'Nope'], 'category'],
    'missing reactions' => [['reactions' => []], 'reactions'],
    'invalid reaction' => [['reactions' => ['Nope']], 'reactions.0'],
    'missing severity' => [['severity' => ''], 'severity'],
    'invalid severity' => [['severity' => 'Nope'], 'severity'],
    'missing status' => [['status' => ''], 'status'],
    'invalid status' => [['status' => 'Nope'], 'status'],
    'invalid onset date' => [['onset_on' => 'not-a-date'], 'onset_on'],
]);

it('allows an allergy without an onset date', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.allergies.store', $patient), allergyPayload(['onset_on' => null]))
        ->assertSessionHasNoErrors();

    expect(PatientAllergy::firstOrFail()->onset_on)->toBeNull();
});

it('removes an allergy from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $allergy = PatientAllergy::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.allergies.destroy', [$patient, $allergy]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientAllergy::find($allergy->id))->toBeNull()
        ->and(PatientAllergy::withTrashed()->find($allergy->id))->not->toBeNull();
});

it('scopes the allergy to its patient in the route binding', function (): void {
    $patient_a = Patient::factory()->create();
    $patient_b = Patient::factory()->create();
    $allergy = PatientAllergy::factory()->create(['patient_id' => $patient_a->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.allergies.destroy', [$patient_b, $allergy]))
        ->assertNotFound();

    expect(PatientAllergy::find($allergy->id))->not->toBeNull();
});

it('includes the allergies list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'allergen' => 'Peanuts',
        'category' => AllergenCategory::Food,
        'reactions' => [AllergyReaction::Anaphylaxis->value],
        'severity' => AllergySeverity::LifeThreatening,
        'status' => AllergyStatus::Active,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('patient_allergies', 1)
            ->has('allergy_reaction_options')
            ->has('allergy_severity_options')
            ->where('patient_allergies.0.allergen', 'Peanuts')
            ->where('patient_allergies.0.category', AllergenCategory::Food->value)
            ->where('patient_allergies.0.reaction_labels.0', AllergyReaction::Anaphylaxis->label())
            ->where('patient_allergies.0.severity', AllergySeverity::LifeThreatening->value)
            ->where('patient_allergies.0.is_critical', true)
        );
});

it('marks an unreviewed empty allergy list as no known allergies', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.allergies.review', $patient))
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $patient->refresh();

    expect($patient->allergies_reviewed_at)->not->toBeNull()
        ->and($patient->allergies_reviewed_by)->toBe($this->staff->id)
        ->and($patient->hasNoKnownAllergies())->toBeTrue();
});

it('distinguishes an unreviewed list from a confirmed no known allergies list', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('allergy_banner.no_known_allergies', false)
            ->where('allergy_banner.reviewed_at', null)
            ->has('allergy_banner.allergies', 0)
        );

    $this->actingAs($this->staff)->post(route('patients.allergies.review', $patient));

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('allergy_banner.no_known_allergies', true)
            ->where('allergy_banner.reviewed_by', trim("{$this->staff->first_name} {$this->staff->last_name}"))
            ->has('allergy_banner.allergies', 0)
        );
});

it('orders the banner worst-first and flags a critical list', function (): void {
    $patient = Patient::factory()->create();

    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'allergen' => 'Dust Mites',
        'severity' => AllergySeverity::Mild,
    ]);
    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'allergen' => 'Peanuts',
        'severity' => AllergySeverity::LifeThreatening,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('allergy_banner.is_critical', true)
            ->where('allergy_banner.allergies.0.allergen', 'Peanuts')
            ->where('allergy_banner.allergies.1.allergen', 'Dust Mites')
        );
});

it('keeps non-current allergies off the banner but on the chart list', function (): void {
    $patient = Patient::factory()->create();

    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'allergen' => 'Amoxicillin',
        'status' => AllergyStatus::Resolved,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->has('patient_allergies', 1)
            ->has('allergy_banner.allergies', 0)
        );
});

it('reports no known allergies only once every allergy is off the current list', function (): void {
    $patient = Patient::factory()->create();
    PatientAllergy::factory()->create([
        'patient_id' => $patient->id,
        'status' => AllergyStatus::Active,
    ]);

    $this->actingAs($this->staff)->post(route('patients.allergies.review', $patient));

    expect($patient->refresh()->hasNoKnownAllergies())->toBeFalse();
});

it('requires authentication to record an allergy', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('patients.allergies.store', $patient), allergyPayload())
        ->assertRedirect(route('login'));

    expect(PatientAllergy::count())->toBe(0);
});
