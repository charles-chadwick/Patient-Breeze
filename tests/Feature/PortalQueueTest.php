<?php

use App\Enums\UserRole;
use App\Models\PortalNotification;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('redirects unauthenticated requests to login', function (): void {
    $this->get(route('portal-queue.index'))
        ->assertRedirect(route('login'));
});

it('renders the portal queue for staff', function (): void {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    PortalNotification::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('portal-queue.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PortalQueue/Index')
            ->has('notifications', 3)
            ->where('unread_count', 3)
        );
});

it('marks a notification as read', function (): void {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $notification = PortalNotification::factory()->create();

    $this->actingAs($user)
        ->post(route('portal-queue.read', $notification))
        ->assertRedirect();

    expect($notification->refresh()->read_at)->not->toBeNull();
});
