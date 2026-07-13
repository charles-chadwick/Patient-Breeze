<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

it('scopes the patient history deferred prop to that patient', function (): void {
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    // Warm up the Inertia version resolver, then reload only the deferred prop.
    $this->get(route('patients.show', $patientA))->assertOk();

    $response = $this->get(route('patients.show', $patientA), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => Inertia::getVersion(),
        'X-Inertia-Partial-Component' => 'Patients/Show',
        'X-Inertia-Partial-Data' => 'history',
    ]);

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data')
            ->where('history.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['subject_id'] === $patientA->id || $row['subject_type'] !== Patient::class
            ))
        );

    // The activity for patient B must not appear in patient A's history.
    $bActivityId = Activity::where('subject_type', Patient::class)->where('subject_id', $patientB->id)->value('id');
    $response->assertInertia(fn ($page) => $page
        ->where('history.data', fn ($rows) => collect($rows)->doesntContain('id', $bActivityId))
    );
});

it('backfills patient_id for existing activity rows', function (): void {
    $patient = Patient::factory()->create();

    // Simulate a legacy row with no patient_id.
    Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)
        ->update(['patient_id' => null]);

    $this->artisan('audit:backfill-patient-id')->assertSuccessful();

    expect(Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)->value('patient_id'))
        ->toBe($patient->id);
});
