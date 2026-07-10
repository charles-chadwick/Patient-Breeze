<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

test('a staff user can switch between the appointments and encounters tabs', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Appointments and Encounters share one block. Appointments is the default;
    // switch to Encounters and assert its action button mounts. Uses stable
    // data-testid selectors rather than translated copy, matching NotesTabTest.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="patient-tab-encounters"]')
        ->assertVisible('[data-testid="new-encounter-note-button"]')
        ->assertNoJavascriptErrors();
})->group('browser');

test('a staff user can switch between the medications and documents tabs', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Medications and Documents share their own block. Medications is the
    // default; switch across each tab and assert the tab-specific action button
    // mounts. Stable data-testid selectors, matching NotesTabTest.
    $page->assertNoJavascriptErrors()
        ->assertVisible('[data-testid="medication-add-button"]')
        ->click('[data-testid="records-tab-documents"]')
        ->assertVisible('[data-testid="document-upload-button"]')
        ->assertNoJavascriptErrors();
})->group('browser');
