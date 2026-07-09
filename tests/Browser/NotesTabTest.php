<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('a staff user can open the notes tab and launch the note editor', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['view_patients', 'view_notes', 'create_notes']);

    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Keep this focused on "tab opens, editor mounts, no JS errors" rather than
    // asserting on translated copy, which can render as raw i18n keys depending
    // on the environment (see AuthorizationModalTest for the same constraint).
    // Use stable data-testid selectors instead of relying on rendered text.
    // Opening the modal mounts the Quill editor (.ql-editor), which is the
    // riskiest new dependency — asserting it renders guards against a hard
    // runtime break shipping unnoticed.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="patient-tab-notes"]')
        ->click('[data-testid="new-note-button"]')
        ->assertVisible('.ql-editor')
        ->assertNoJavascriptErrors();
})->group('browser');
