<?php

use App\Enums\UserRole;
use App\Enums\VaccineStatus;
use App\Models\Patient;
use App\Models\PatientVaccine;
use App\Models\User;
use App\Models\Vaccine;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

test('a staff user can open the vaccines tab and see recorded doses', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    PatientVaccine::factory()->create([
        'patient_id' => $patient->id,
        'vaccine' => 'Tdap',
        'cvx_code' => '115',
        'status' => VaccineStatus::Completed,
        'administered_by' => $user->id,
    ]);

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // The vaccines block shares the Care tab strip with medications; switch to
    // it and assert the recorded dose renders alongside the add button.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="records-tab-vaccines"]')
        ->assertVisible('[data-testid="vaccine-add-button"]')
        ->assertVisible('[data-testid="vaccine-row"]')
        ->assertSee('Tdap')
        ->assertSee('115')
        ->assertNoJavascriptErrors();
})->group('browser');

test('a staff user can record a vaccine through the catalog search modal', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    Vaccine::factory()->create(['name' => 'Varicella', 'cvx_code' => '21']);

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Drive the two-step modal end to end: search the catalog, pick a result,
    // then submit the administration form the selection pre-fills.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="records-tab-vaccines"]')
        ->click('[data-testid="vaccine-add-button"]')
        ->type('[data-testid="vaccine-search-input"]', 'Varicella')
        ->waitForText('Varicella')
        ->click('[data-testid="vaccine-search-result"]')
        ->assertVisible('[data-testid="vaccine-administered-on"]')
        ->click('button[form="patient-vaccine-form"]')
        ->waitForText('Varicella')
        ->assertNoJavascriptErrors();

    $vaccine = PatientVaccine::firstOrFail();

    expect($vaccine->vaccine)->toBe('Varicella')
        ->and($vaccine->cvx_code)->toBe('21')
        ->and($vaccine->patient_id)->toBe($patient->id)
        ->and($vaccine->administered_by)->toBe($user->id);
})->group('browser');

test('a staff user can browse the vaccine catalog admin page', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();

    Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);

    $this->actingAs($user);

    $page = visit(route('vaccines.index'));

    $page->assertNoJavascriptErrors()
        ->assertSee('Tdap')
        ->assertSee('115')
        ->assertNoJavascriptErrors();
})->group('browser');
