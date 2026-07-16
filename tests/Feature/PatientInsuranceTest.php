<?php

use App\Enums\InsurancePlanType;
use App\Enums\InsurancePriority;
use App\Enums\SubscriberRelationship;
use App\Enums\UserRole;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
    $this->company = InsuranceCompany::factory()->create();
});

function patientInsurancePayload(InsuranceCompany $company, array $overrides = []): array
{
    return array_merge([
        'insurance_company_id' => $company->id,
        'member_id' => 'XYZ123456789',
        'group_number' => 'GRP-0001',
        'plan_type' => InsurancePlanType::Ppo->value,
        'priority' => InsurancePriority::Primary->value,
        'subscriber_name' => 'Jordan Rivera',
        'relationship_to_subscriber' => SubscriberRelationship::Self->value,
        'effective_on' => '2026-01-01',
        'terminates_on' => null,
        'notes' => 'Verified at intake.',
    ], $overrides);
}

it('records insurance on a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.insurances.store', $patient), patientInsurancePayload($this->company))
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $insurance = PatientInsurance::firstOrFail();

    expect($insurance->patient_id)->toBe($patient->id)
        ->and($insurance->insurance_company_id)->toBe($this->company->id)
        ->and($insurance->member_id)->toBe('XYZ123456789')
        ->and($insurance->group_number)->toBe('GRP-0001')
        ->and($insurance->plan_type)->toBe(InsurancePlanType::Ppo)
        ->and($insurance->priority)->toBe(InsurancePriority::Primary)
        ->and($insurance->relationship_to_subscriber)->toBe(SubscriberRelationship::Self)
        ->and($insurance->effective_on->toDateString())->toBe('2026-01-01');
});

it('records insurance with only the required fields', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.insurances.store', $patient), [
            'insurance_company_id' => $this->company->id,
            'member_id' => 'ABC999',
            'priority' => InsurancePriority::Secondary->value,
            'relationship_to_subscriber' => SubscriberRelationship::Spouse->value,
        ])
        ->assertSessionHasNoErrors();

    $insurance = PatientInsurance::firstOrFail();

    expect($insurance->priority)->toBe(InsurancePriority::Secondary)
        ->and($insurance->plan_type)->toBeNull()
        ->and($insurance->group_number)->toBeNull();
});

it('validates the patient insurance payload', function (array $overrides, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.insurances.store', $patient), patientInsurancePayload($this->company, $overrides))
        ->assertSessionHasErrors($invalidField);

    expect(PatientInsurance::count())->toBe(0);
})->with([
    'missing company' => [['insurance_company_id' => null], 'insurance_company_id'],
    'unknown company' => [['insurance_company_id' => 999999], 'insurance_company_id'],
    'missing member id' => [['member_id' => ''], 'member_id'],
    'missing priority' => [['priority' => ''], 'priority'],
    'invalid priority' => [['priority' => 'Fourth'], 'priority'],
    'invalid plan type' => [['plan_type' => 'Nope'], 'plan_type'],
    'invalid relationship' => [['relationship_to_subscriber' => 'Cousin'], 'relationship_to_subscriber'],
    'termination before effective' => [['effective_on' => '2026-05-01', 'terminates_on' => '2026-01-01'], 'terminates_on'],
]);

it('removes an insurance record from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $insurance = PatientInsurance::factory()->create([
        'patient_id' => $patient->id,
        'insurance_company_id' => $this->company->id,
    ]);

    $this->actingAs($this->staff)
        ->delete(route('patients.insurances.destroy', [$patient, $insurance]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientInsurance::find($insurance->id))->toBeNull()
        ->and(PatientInsurance::withTrashed()->find($insurance->id))->not->toBeNull();
});

it('scopes the insurance to its patient in the route binding', function (): void {
    $patient_a = Patient::factory()->create();
    $patient_b = Patient::factory()->create();
    $insurance = PatientInsurance::factory()->create([
        'patient_id' => $patient_a->id,
        'insurance_company_id' => $this->company->id,
    ]);

    $this->actingAs($this->staff)
        ->delete(route('patients.insurances.destroy', [$patient_b, $insurance]))
        ->assertNotFound();

    expect(PatientInsurance::find($insurance->id))->not->toBeNull();
});

it('includes the insurances and option lists in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientInsurance::factory()->create([
        'patient_id' => $patient->id,
        'insurance_company_id' => $this->company->id,
        'priority' => InsurancePriority::Primary,
        'member_id' => 'MEMBER-1',
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('insurances', 1)
            ->has('insurance_plan_type_options')
            ->has('insurance_priority_options')
            ->has('subscriber_relationship_options')
            ->where('insurances.0.member_id', 'MEMBER-1')
            ->where('insurances.0.company_name', $this->company->name)
            ->where('insurances.0.is_active', true)
        );
});

it('orders the chart list by priority', function (): void {
    $patient = Patient::factory()->create();

    PatientInsurance::factory()->priority(InsurancePriority::Secondary)->create([
        'patient_id' => $patient->id,
        'insurance_company_id' => $this->company->id,
        'member_id' => 'SECOND',
    ]);
    PatientInsurance::factory()->priority(InsurancePriority::Primary)->create([
        'patient_id' => $patient->id,
        'insurance_company_id' => $this->company->id,
        'member_id' => 'FIRST',
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('insurances.0.member_id', 'FIRST')
            ->where('insurances.1.member_id', 'SECOND')
        );
});

it('marks a terminated policy inactive', function (): void {
    $active = PatientInsurance::factory()->create([
        'insurance_company_id' => $this->company->id,
        'effective_on' => '2026-01-01',
        'terminates_on' => null,
    ]);
    $expired = PatientInsurance::factory()->create([
        'insurance_company_id' => $this->company->id,
        'effective_on' => '2024-01-01',
        'terminates_on' => '2025-01-01',
    ]);

    expect($active->isActive())->toBeTrue()
        ->and($expired->isActive())->toBeFalse();
});

it('requires authentication to record insurance', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('patients.insurances.store', $patient), patientInsurancePayload($this->company))
        ->assertRedirect(route('login'));

    expect(PatientInsurance::count())->toBe(0);
});
