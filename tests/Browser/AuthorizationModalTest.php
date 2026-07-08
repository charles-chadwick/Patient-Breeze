<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('an unauthorized user sees the not-authorized modal when scheduling an appointment', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('view_patients');

    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Use a CSS selector instead of the link text: Pest's locator guesser misparses
    // the "+" in "+ New Appointment" as CSS combinator syntax and times out.
    $page->assertSee('+ New Appointment')
        ->click('a[href*="appointments/create"]')
        ->assertSee('Not Authorized')
        ->assertSee('You are not authorized to perform this action.')
        ->assertNoJavascriptErrors();
})->group('browser');
