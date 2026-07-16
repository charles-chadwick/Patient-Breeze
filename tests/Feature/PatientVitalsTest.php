<?php

use App\Enums\BodyPosition;
use App\Enums\OxygenDelivery;
use App\Enums\TemperatureSite;
use App\Enums\UserRole;
use App\Enums\VitalType;
use App\Models\Patient;
use App\Models\PatientVitals;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

function vitalsPayload(array $overrides = []): array
{
    return array_merge([
        'measured_at' => '2026-05-01 09:30:00',
        'systolic' => 122,
        'diastolic' => 78,
        'position' => BodyPosition::Sitting->value,
        'heart_rate' => 72,
        'respiratory_rate' => 16,
        'temperature' => 37.0,
        'temperature_site' => TemperatureSite::Oral->value,
        'oxygen_saturation' => 98,
        'oxygen_delivery' => OxygenDelivery::RoomAir->value,
        'weight' => 80.0,
        'height' => 180.0,
        'pain_score' => 2,
        'notes' => 'Patient comfortable.',
    ], $overrides);
}

it('records a vitals set on a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), vitalsPayload())
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $vitals = PatientVitals::firstOrFail();

    expect($vitals->patient_id)->toBe($patient->id)
        ->and($vitals->systolic)->toBe(122)
        ->and($vitals->diastolic)->toBe(78)
        ->and($vitals->position)->toBe(BodyPosition::Sitting)
        ->and($vitals->heart_rate)->toBe(72)
        ->and($vitals->respiratory_rate)->toBe(16)
        ->and((float) $vitals->temperature)->toBe(37.0)
        ->and($vitals->temperature_site)->toBe(TemperatureSite::Oral)
        ->and($vitals->oxygen_saturation)->toBe(98)
        ->and($vitals->oxygen_delivery)->toBe(OxygenDelivery::RoomAir)
        ->and((float) $vitals->weight)->toBe(80.0)
        ->and((float) $vitals->height)->toBe(180.0)
        ->and($vitals->pain_score)->toBe(2)
        ->and($vitals->measured_at->toDateTimeString())->toBe('2026-05-01 09:30:00');
});

it('attributes the readings to the recording user by default', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), vitalsPayload());

    expect(PatientVitals::firstOrFail()->recorded_by)->toBe($this->staff->id);
});

it('attributes the readings to another clinician when one is named', function (): void {
    $patient = Patient::factory()->create();
    $nurse = User::factory()->withRole(UserRole::Nurse)->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), vitalsPayload([
            'recorded_by' => $nurse->id,
        ]));

    expect(PatientVitals::firstOrFail()->recorded_by)->toBe($nurse->id);
});

it('records a set with only a single measurement', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), [
            'measured_at' => '2026-05-01 09:30:00',
            'heart_rate' => 68,
        ])
        ->assertSessionHasNoErrors();

    expect(PatientVitals::firstOrFail()->heart_rate)->toBe(68);
});

it('rejects a set with no measurements at all', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), [
            'measured_at' => '2026-05-01 09:30:00',
            'notes' => 'Refused all readings.',
        ])
        ->assertSessionHasErrors('measured_at');

    expect(PatientVitals::count())->toBe(0);
});

it('validates the vitals payload', function (array $overrides, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.vitals.store', $patient), vitalsPayload($overrides))
        ->assertSessionHasErrors($invalidField);

    expect(PatientVitals::count())->toBe(0);
})->with([
    'missing date' => [['measured_at' => ''], 'measured_at'],
    'invalid date' => [['measured_at' => 'not-a-date'], 'measured_at'],
    'systolic too high' => [['systolic' => 400], 'systolic'],
    'negative heart rate' => [['heart_rate' => -5], 'heart_rate'],
    'temperature too low' => [['temperature' => 10], 'temperature'],
    'oxygen over 100' => [['oxygen_saturation' => 120], 'oxygen_saturation'],
    'pain out of scale' => [['pain_score' => 11], 'pain_score'],
    'invalid position' => [['position' => 'Nope'], 'position'],
    'invalid temperature site' => [['temperature_site' => 'Nope'], 'temperature_site'],
    'invalid oxygen delivery' => [['oxygen_delivery' => 'Nope'], 'oxygen_delivery'],
    'unknown recorder' => [['recorded_by' => 999999], 'recorded_by'],
]);

it('removes a vitals set from a patient chart', function (): void {
    $patient = Patient::factory()->create();
    $vitals = PatientVitals::factory()->create(['patient_id' => $patient->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.vitals.destroy', [$patient, $vitals]))
        ->assertRedirect(route('patients.show', $patient));

    expect(PatientVitals::find($vitals->id))->toBeNull()
        ->and(PatientVitals::withTrashed()->find($vitals->id))->not->toBeNull();
});

it('scopes the vitals set to its patient in the route binding', function (): void {
    $patient_a = Patient::factory()->create();
    $patient_b = Patient::factory()->create();
    $vitals = PatientVitals::factory()->create(['patient_id' => $patient_a->id]);

    $this->actingAs($this->staff)
        ->delete(route('patients.vitals.destroy', [$patient_b, $vitals]))
        ->assertNotFound();

    expect(PatientVitals::find($vitals->id))->not->toBeNull();
});

it('includes the vitals and flowsheet metadata in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    PatientVitals::factory()->create([
        'patient_id' => $patient->id,
        'systolic' => 118,
        'diastolic' => 76,
        'weight' => 80.0,
        'height' => 200.0,
        'recorded_by' => $this->staff->id,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('vitals', 1)
            ->has('vital_types', count(VitalType::cases()))
            ->has('body_position_options')
            ->has('temperature_site_options')
            ->has('oxygen_delivery_options')
            ->where('vitals.0.blood_pressure', '118/76')
            ->where('vitals.0.bmi', 20)
            ->where('vitals.0.recorded_by.id', $this->staff->id)
        );
});

it('orders the chart list with the most recent set first', function (): void {
    $patient = Patient::factory()->create();

    PatientVitals::factory()->create([
        'patient_id' => $patient->id,
        'measured_at' => '2024-01-01 08:00:00',
        'heart_rate' => 60,
    ]);
    PatientVitals::factory()->create([
        'patient_id' => $patient->id,
        'measured_at' => '2026-01-01 08:00:00',
        'heart_rate' => 90,
    ]);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->where('vitals.0.heart_rate', 90)
            ->where('vitals.1.heart_rate', 60)
        );
});

it('derives bmi from weight and height', function (): void {
    $vitals = PatientVitals::factory()->make([
        'weight' => 81.0,
        'height' => 180.0,
    ]);

    // 81 / 1.8^2 = 25.0
    expect($vitals->bmi)->toBe(25.0);
});

it('leaves bmi null when a measurement is missing', function (): void {
    $vitals = PatientVitals::factory()->make(['weight' => 80.0, 'height' => null]);

    expect($vitals->bmi)->toBeNull();
});

it('flags readings that fall outside the adult normal range', function (): void {
    $vitals = PatientVitals::factory()->make([
        'systolic' => 160,
        'diastolic' => 80,
        'heart_rate' => 72,
        'respiratory_rate' => 16,
        'temperature' => 39.0,
        'oxygen_saturation' => 90,
    ]);

    $flags = $vitals->abnormalFlags();

    expect($flags)->toContain(VitalType::Systolic->value)
        ->toContain(VitalType::Temperature->value)
        ->toContain(VitalType::OxygenSaturation->value)
        ->not->toContain(VitalType::Diastolic->value)
        ->not->toContain(VitalType::HeartRate->value);
});

it('requires authentication to record vitals', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('patients.vitals.store', $patient), vitalsPayload())
        ->assertRedirect(route('login'));

    expect(PatientVitals::count())->toBe(0);
});
