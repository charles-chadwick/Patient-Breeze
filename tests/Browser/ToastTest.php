<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('saving preferences shows a success toast', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit(route('settings.index'));

    // The preferences form flashes `flash.settings.updated` on save, which the
    // server resolves to its English string. The <Toaster> mounted in the
    // dashboard layout surfaces that flash as a toast.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="save-preferences"]')
        ->assertSee('Preferences saved successfully.')
        ->assertNoJavascriptErrors();
});
