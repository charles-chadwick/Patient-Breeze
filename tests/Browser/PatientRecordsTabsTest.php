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

test('a staff user can switch between the appointments, medications, and documents tabs', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Appointments is the default records tab. Switch across each tab and assert
    // the tab-specific action button mounts. Uses stable data-testid selectors
    // rather than translated copy, matching the constraint in NotesTabTest.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="records-tab-medications"]')
        ->assertVisible('[data-testid="medication-add-button"]')
        ->click('[data-testid="records-tab-documents"]')
        ->assertVisible('[data-testid="document-upload-button"]')
        ->assertNoJavascriptErrors();
})->group('browser');
