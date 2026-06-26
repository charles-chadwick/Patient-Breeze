<?php

use App\Enums\DiscussionPostStatus;
use App\Enums\UserRole;
use App\Events\DiscussionPostCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->user = User::factory()->withRole(UserRole::Staff)->create();
    $this->actingAs($this->user);
});

it('creates a post in a discussion', function (): void {
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    $response = $this->post(route('discussions.posts.store', $discussion), [
        'content' => 'This patient responded well to treatment.',
    ]);

    $response->assertRedirect();
    expect(DiscussionPost::count())->toBe(1)
        ->and(DiscussionPost::first()->content)->toBe('This patient responded well to treatment.')
        ->and(DiscussionPost::first()->user_id)->toBe($this->user->id)
        ->and(DiscussionPost::first()->status)->toBe(DiscussionPostStatus::Published);
});

it('broadcasts a DiscussionPostCreated event on the discussion channel', function (): void {
    Event::fake([DiscussionPostCreated::class]);

    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    $this->post(route('discussions.posts.store', $discussion), [
        'content' => 'Notifying the rest of the care team.',
    ]);

    Event::assertDispatched(DiscussionPostCreated::class, function (DiscussionPostCreated $event) use ($discussion) {
        return $event->post->discussion_id === $discussion->id
            && collect($event->broadcastOn())->contains(
                fn (PrivateChannel $channel) => $channel->name === 'private-discussion.'.$discussion->id
            );
    });
});

it('validates that content is required', function (): void {
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    $response = $this->post(route('discussions.posts.store', $discussion), []);

    $response->assertSessionHasErrors(['content']);
});
