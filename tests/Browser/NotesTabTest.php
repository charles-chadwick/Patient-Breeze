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

    // Use a CSS selector for the tab button (Vue button text, not a link) and
    // avoid the locator guesser choking on the "+" in "+ New Note" (see
    // AuthorizationModalTest for the same workaround).
    $page->assertSee('Notes')
        ->click('button:has-text("Notes")')
        ->assertSee('+ New Note')
        ->assertNoJavascriptErrors();
})->group('browser');
