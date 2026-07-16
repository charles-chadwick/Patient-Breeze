<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientVitals;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

test('a staff user can open the vitals tab and see the flowsheet', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    PatientVitals::factory()->create([
        'patient_id' => $patient->id,
        'systolic' => 118,
        'diastolic' => 76,
        'heart_rate' => 72,
        'recorded_by' => $user->id,
    ]);

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Vitals is the default Care tab, so the block should already be present.
    $page->assertNoJavascriptErrors()
        ->assertVisible('[data-testid="vitals-add-button"]')
        ->assertSee('118/76')
        ->assertNoJavascriptErrors();
})->group('browser');

test('a staff user can record a vitals set through the modal', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Drive the direct-entry modal: open it, enter a couple of readings, submit.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="vitals-add-button"]')
        ->assertVisible('[data-testid="vitals-measured-at"]')
        ->type('input[max="400"]', '68')
        ->click('button[form="patient-vitals-form"]')
        ->waitForText('Flowsheet')
        ->assertNoJavascriptErrors();

    $vitals = PatientVitals::firstOrFail();

    expect($vitals->heart_rate)->toBe(68)
        ->and($vitals->patient_id)->toBe($patient->id)
        ->and($vitals->recorded_by)->toBe($user->id);
})->group('browser');
