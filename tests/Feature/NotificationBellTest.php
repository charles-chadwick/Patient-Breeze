<?php

use App\Models\Patient;
use App\Models\User;
use App\Notifications\PortalMessageReceived;
use Illuminate\Support\Str;

function makeUserNotification(User $user, array $data): string
{
    $id = (string) Str::uuid();

    $user->notifications()->create([
        'id' => $id,
        'type' => PortalMessageReceived::class,
        'data' => $data,
    ]);

    return $id;
}

it('shares the user notifications and unread count on every page', function (): void {
    $user = User::factory()->create();
    makeUserNotification($user, ['title' => 'Hi', 'body' => 'Body', 'url' => route('dashboard')]);

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread_count', 1)
            ->has('notifications.items', 1)
            ->where('notifications.items.0.title', 'Hi')
        );
});

it('marks a notification read and redirects to its target when opened', function (): void {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $target = route('patients.show', $patient).'?tab=discussions&discussion=1';

    $id = makeUserNotification($user, ['title' => 't', 'body' => 'b', 'url' => $target]);

    $this->actingAs($user)
        ->get(route('notifications.open', $id))
        ->assertRedirect($target);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('does not let a user open another user\'s notification', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $id = makeUserNotification($owner, ['title' => 't', 'url' => route('dashboard')]);

    $this->actingAs($other)
        ->get(route('notifications.open', $id))
        ->assertNotFound();
});

it('marks all notifications read', function (): void {
    $user = User::factory()->create();
    makeUserNotification($user, ['title' => 'a']);
    makeUserNotification($user, ['title' => 'b']);

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
