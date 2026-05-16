<?php

use App\Models\Patient;

it('shows the portal login page', function (): void {
    $this->get(route('portal.login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Portal/Login'));
});

it('redirects unauthenticated users to the portal login page', function (): void {
    $this->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));
});

it('redirects to dashboard when portal patient is already authenticated', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.login'))
        ->assertRedirect(route('portal.dashboard'));
});

it('authenticates a patient with valid credentials', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->post(route('portal.login'), [
        'email' => $patient->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($patient, 'portal');
});

it('rejects invalid credentials', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->post(route('portal.login'), [
        'email' => $patient->email,
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('portal');
});

it('logs out a portal patient and redirects to the login page', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.logout'))
        ->assertRedirect(route('portal.login'));

    $this->assertGuest('portal');
});
