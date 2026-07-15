<?php

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('renders a branded Inertia error page for unauthorized actions', function (): void {
    // Nurse, not Doctor: Doctors hold full grants and can reach the audit log.
    $this->actingAs(User::factory()->withRole(UserRole::Nurse)->create());

    $this->get(route('audit-log.index'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 403)
        );
});

it('exposes the "no access" copy for the 403 status', function (): void {
    expect(__('errors.status.403.description'))
        ->toBe('You do not have access to this feature.');
});
