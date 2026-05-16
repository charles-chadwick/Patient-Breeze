<?php

use App\Models\Patient;

it('redirects unauthenticated patients to the portal login', function (): void {
    $this->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));
});

it('renders the dashboard for an authenticated patient', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Dashboard')
            ->has('patient')
            ->has('appointments')
            ->has('discussions')
            ->has('documents')
        );
});
