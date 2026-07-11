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

function makePatientDiscussion(): Discussion
{
    $patient = Patient::factory()->create();

    return Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);
}

it("updates the author's own post", function (): void {
    $discussion = makePatientDiscussion();
    $post = DiscussionPost::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $this->user->id,
        'content' => 'Original message.',
    ]);

    $response = $this->put(route('discussions.posts.update', [$discussion, $post]), [
        'content' => 'Corrected message.',
    ]);

    $response->assertRedirect();
    expect($post->fresh()->content)->toBe('Corrected message.');
});

it("forbids editing another user's post", function (): void {
    $discussion = makePatientDiscussion();
    $post = DiscussionPost::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => User::factory()->withRole(UserRole::Staff)->create()->id,
        'content' => 'Someone else wrote this.',
    ]);

    $response = $this->put(route('discussions.posts.update', [$discussion, $post]), [
        'content' => 'Tampered.',
    ]);

    $response->assertForbidden();
    expect($post->fresh()->content)->toBe('Someone else wrote this.');
});

it("soft-deletes the author's own post", function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $this->actingAs($doctor);

    $discussion = makePatientDiscussion();
    $post = DiscussionPost::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $doctor->id,
    ]);

    $response = $this->delete(route('discussions.posts.destroy', [$discussion, $post]));

    $response->assertRedirect();
    $this->assertSoftDeleted($post);
});

it('forbids deleting a post without the delete permission', function (): void {
    // The default acting user (Staff) owns the post but lacks delete_discussions.
    $discussion = makePatientDiscussion();
    $post = DiscussionPost::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->delete(route('discussions.posts.destroy', [$discussion, $post]));

    $response->assertForbidden();
    expect($post->fresh()->trashed())->toBeFalse();
});

it("forbids deleting another user's post even with the delete permission", function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $discussion = makePatientDiscussion();
    $post = DiscussionPost::factory()->create([
        'discussion_id' => $discussion->id,
        'user_id' => User::factory()->withRole(UserRole::Staff)->create()->id,
    ]);

    $response = $this->delete(route('discussions.posts.destroy', [$discussion, $post]));

    $response->assertForbidden();
    expect($post->fresh()->trashed())->toBeFalse();
});
