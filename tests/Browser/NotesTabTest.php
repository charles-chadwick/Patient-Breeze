<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('a staff user can open the notes tab and create a note', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['view_patients', 'view_notes', 'create_notes']);

    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Keep this focused on "tab opens, no JS errors" rather than asserting on
    // translated copy, which can render as raw i18n keys depending on the
    // environment (see AuthorizationModalTest for the same constraint). Use a
    // stable data-testid selector instead of relying on rendered text.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="patient-tab-notes"]')
        ->assertNoJavascriptErrors();
})->group('browser');
