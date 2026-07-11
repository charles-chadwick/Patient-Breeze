<?php

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Enums\UserRole;
use App\Models\Discussion;
use App\Models\DiscussionParticipant;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->user = User::factory()->withRole(UserRole::Staff)->create();
    $this->actingAs($this->user);
});

it('creates a discussion for a patient', function (): void {
    $patient = Patient::factory()->create();

    $response = $this->post(route('discussions.store'), [
        'title' => 'Follow-up re: medications',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'initial_reply' => 'Opening message.',
    ]);

    $response->assertRedirect();
    expect(Discussion::count())->toBe(1)
        ->and(Discussion::first()->title)->toBe('Follow-up re: medications')
        ->and(Discussion::first()->status)->toBe('Open')
        ->and(Discussion::first()->discussionable_id)->toBe($patient->id);
});

it('auto-adds the current user as initiator participant', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('discussions.store'), [
        'title' => 'Test discussion',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'initial_reply' => 'Opening message.',
    ]);

    expect(DiscussionParticipant::count())->toBe(1)
        ->and(DiscussionParticipant::first()->participantable_id)->toBe($this->user->id)
        ->and(DiscussionParticipant::first()->is_initiator)->toBeTrue();
});

it('adds additional participants to the discussion', function (): void {
    $patient = Patient::factory()->create();
    $other_user = User::factory()->withRole(UserRole::Staff)->create();

    $this->post(route('discussions.store'), [
        'title' => 'Team discussion',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'participant_ids' => [$other_user->id],
        'initial_reply' => 'Opening message.',
    ]);

    $discussion = Discussion::first();

    expect(DiscussionParticipant::count())->toBe(2)
        ->and($discussion->participants()->where('is_initiator', false)->first()->participantable_id)->toBe($other_user->id);
});

it('does not duplicate the current user when included in participant_ids', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('discussions.store'), [
        'title' => 'Test',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'participant_ids' => [$this->user->id],
        'initial_reply' => 'Opening message.',
    ]);

    expect(DiscussionParticipant::count())->toBe(1);
});

it('validates required fields', function (): void {
    $response = $this->post(route('discussions.store'), []);

    $response->assertSessionHasErrors(['title', 'type', 'discussionable_type', 'discussionable_id', 'initial_reply']);
});

it('rejects unknown discussionable types', function (): void {
    $response = $this->post(route('discussions.store'), [
        'title' => 'Test',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => 'App\\Models\\Malicious',
        'discussionable_id' => 1,
        'initial_reply' => 'Opening message.',
    ]);

    $response->assertSessionHasErrors(['discussionable_type']);
});

it('auto-adds the patient as a participant for portal messages', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('discussions.store'), [
        'title' => 'Message from patient',
        'type' => DiscussionType::PortalMessage->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'initial_reply' => 'Opening message.',
    ]);

    $discussion = Discussion::first();

    expect(DiscussionParticipant::count())->toBe(2)
        ->and($discussion->participants()->where('is_initiator', false)->first()->participantable_type)->toBe(Patient::class)
        ->and($discussion->participants()->where('is_initiator', false)->first()->participantable_id)->toBe($patient->id);
});

it('stores the correct type on the discussion', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('discussions.store'), [
        'title' => 'Test',
        'type' => DiscussionType::PortalMessage->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'initial_reply' => 'Opening message.',
    ]);

    expect(Discussion::first()->type)->toBe(DiscussionType::PortalMessage);
});

it('creates an initial post from the initial reply', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('discussions.store'), [
        'title' => 'Test',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
        'initial_reply' => 'This is my opening message.',
    ]);

    expect(DiscussionPost::count())->toBe(1)
        ->and(DiscussionPost::first()->content)->toBe('This is my opening message.')
        ->and(DiscussionPost::first()->user_id)->toBe($this->user->id)
        ->and(DiscussionPost::first()->status)->toBe(DiscussionPostStatus::Published);
});

it('soft-deletes a discussion for a user with the delete permission', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    $response = $this->delete(route('discussions.destroy', $discussion));

    $response->assertRedirect();
    $this->assertSoftDeleted($discussion);
});

it('forbids deleting a discussion without the delete permission', function (): void {
    // The default acting user is Staff, whose role lacks delete_discussions.
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    $response = $this->delete(route('discussions.destroy', $discussion));

    $response->assertForbidden();
    expect($discussion->fresh()->trashed())->toBeFalse();
});
