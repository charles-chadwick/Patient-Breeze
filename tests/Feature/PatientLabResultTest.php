<?php

use App\Enums\GenderAtBirth;
use App\Enums\ResultFlag;
use App\Enums\UserRole;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use App\Models\Patient;
use App\Models\PatientLabResult;
use App\Models\User;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

/**
 * A Hemoglobin lab order with adult male/female ranges and a pediatric band.
 */
function hemoglobinOrderWithRanges(): LabOrder
{
    $labOrder = LabOrder::factory()->create([
        'name' => 'Hemoglobin',
        'performing_lab' => 'Hospital Core Laboratory',
        'cpt_code' => '85018',
    ]);

    LabReferenceRange::factory()->for($labOrder)->forGender(GenderAtBirth::Female)->forAges(18, null)
        ->create(['low_value' => '12.0', 'high_value' => '15.5', 'unit' => 'g/dL']);
    LabReferenceRange::factory()->for($labOrder)->forGender(GenderAtBirth::Male)->forAges(18, null)
        ->create(['low_value' => '13.5', 'high_value' => '17.5', 'unit' => 'g/dL']);
    LabReferenceRange::factory()->for($labOrder)->forAges(null, 17)
        ->create(['low_value' => '11.0', 'high_value' => '16.0', 'unit' => 'g/dL']);

    return $labOrder;
}

function patientAged(int $years, GenderAtBirth $gender): Patient
{
    return Patient::factory()->create([
        'gender_at_birth' => $gender,
        'date_of_birth' => Carbon::now()->subYears($years)->subMonth(),
    ]);
}

it('records a result and resolves the range for the patient gender and age', function (): void {
    $labOrder = hemoglobinOrderWithRanges();
    $patient = patientAged(30, GenderAtBirth::Female);

    $this->actingAs($this->staff)
        ->post(route('patients.lab-results.store', $patient), [
            'lab_order_id' => $labOrder->id,
            'value' => '11.0',
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $result = PatientLabResult::firstOrFail();

    expect($result->patient_id)->toBe($patient->id)
        ->and($result->name)->toBe('Hemoglobin')
        ->and($result->cpt_code)->toBe('85018')
        ->and($result->value)->toBe(11.0)
        ->and($result->reference_low)->toBe(12.0)
        ->and($result->reference_high)->toBe(15.5)
        ->and($result->referenceLabel())->toBe('12.0–15.5 g/dL')
        ->and($result->reference_gender)->toBe(GenderAtBirth::Female)
        ->and($result->reference_age)->toBe(30)
        ->and($result->unit)->toBe('g/dL')
        ->and($result->flag)->toBe(ResultFlag::Low);
});

it('flags a value inside the range as normal', function (): void {
    $labOrder = hemoglobinOrderWithRanges();
    $patient = patientAged(30, GenderAtBirth::Female);

    $this->actingAs($this->staff)
        ->post(route('patients.lab-results.store', $patient), [
            'lab_order_id' => $labOrder->id,
            'value' => '13.0',
        ]);

    expect(PatientLabResult::firstOrFail()->flag)->toBe(ResultFlag::Normal);
});

it('resolves the male range for a male patient and flags a high value', function (): void {
    $labOrder = hemoglobinOrderWithRanges();
    $patient = patientAged(40, GenderAtBirth::Male);

    $this->actingAs($this->staff)
        ->post(route('patients.lab-results.store', $patient), [
            'lab_order_id' => $labOrder->id,
            'value' => '18.0',
        ]);

    $result = PatientLabResult::firstOrFail();

    expect($result->reference_low)->toBe(13.5)
        ->and($result->reference_high)->toBe(17.5)
        ->and($result->reference_gender)->toBe(GenderAtBirth::Male)
        ->and($result->flag)->toBe(ResultFlag::High);
});

it('resolves the pediatric range for a child regardless of gender', function (): void {
    $labOrder = hemoglobinOrderWithRanges();
    $patient = patientAged(10, GenderAtBirth::Male);

    $range = $labOrder->resolveReferenceRangeFor($patient);

    expect($range->low_value)->toBe(11.0)
        ->and($range->high_value)->toBe(16.0)
        ->and($range->gender_at_birth)->toBeNull();
});

it('previews the reference range for a patient via the endpoint', function (): void {
    $labOrder = hemoglobinOrderWithRanges();
    $patient = patientAged(30, GenderAtBirth::Female);

    $this->actingAs($this->staff)
        ->getJson(route('patients.lab-results.reference-range', ['patient' => $patient, 'lab_order_id' => $labOrder->id]))
        ->assertOk()
        ->assertJsonPath('gender', 'Female')
        ->assertJsonPath('age', 30)
        ->assertJsonPath('reference_range.gender', 'Female')
        ->assertJsonPath('reference_range.unit', 'g/dL');
});

it('requires a lab order and a value', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.lab-results.store', $patient), [])
        ->assertSessionHasErrors(['lab_order_id', 'value']);

    expect(PatientLabResult::count())->toBe(0);
});

it('records a non-numeric result for a test without a numeric range', function (): void {
    $labOrder = LabOrder::factory()->create(['name' => 'HIV-1/HIV-2 Antigen/Antibody']);
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.lab-results.store', $patient), [
            'lab_order_id' => $labOrder->id,
            'value' => 'Non-reactive',
        ])
        ->assertRedirect(route('patients.show', $patient));

    $result = PatientLabResult::firstOrFail();

    expect($result->value)->toBe('Non-reactive')
        ->and($result->reference_low)->toBeNull()
        ->and($result->reference_high)->toBeNull()
        ->and($result->flag)->toBe(ResultFlag::Unknown);
});

it('casts stored values to their natural type on read', function (): void {
    $float = PatientLabResult::factory()->create(['value' => '12.5']);
    $integer = PatientLabResult::factory()->create(['value' => '7']);
    $qualitative = PatientLabResult::factory()->create(['value' => 'Negative', 'reference_low' => null, 'reference_high' => null]);
    $boolean = PatientLabResult::factory()->create(['value' => 'true']);

    expect($float->fresh()->value)->toBe(12.5)
        ->and($integer->fresh()->value)->toBe(7)
        ->and($qualitative->fresh()->value)->toBe('Negative')
        ->and($qualitative->fresh()->flag)->toBe(ResultFlag::Unknown)
        ->and($boolean->fresh()->value)->toBeTrue();
});

it('removes a lab result from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $result = PatientLabResult::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.lab-results.destroy', [$patient, $result]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientLabResult::find($result->id))->toBeNull()
        ->and(PatientLabResult::withTrashed()->find($result->id))->not->toBeNull();
});

it('scopes the lab result to its patient in the route binding', function (): void {
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $result = PatientLabResult::factory()->create(['patient_id' => $patientA->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.lab-results.destroy', [$patientB, $result]))
        ->assertNotFound();

    expect(PatientLabResult::find($result->id))->not->toBeNull();
});

it('includes the lab results list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientLabResult::factory()->create([
        'patient_id' => $patient->id,
        'value' => 15,
        'reference_low' => 10,
        'reference_high' => 20,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('lab_results', 1)
            ->where('lab_results.0.flag_label', ResultFlag::Normal->label())
        );
});
