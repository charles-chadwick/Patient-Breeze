<?php

use App\Enums\DiscussionType;
use App\Enums\UserRole;
use App\Models\Discussion;
use App\Models\DiscussionParticipant;
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
    ]);

    $discussion = Discussion::first();

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
    ]);

    expect(DiscussionParticipant::count())->toBe(1);
});

it('validates required fields', function (): void {
    $response = $this->post(route('discussions.store'), []);

    $response->assertSessionHasErrors(['title', 'type', 'discussionable_type', 'discussionable_id']);
});

it('rejects unknown discussionable types', function (): void {
    $response = $this->post(route('discussions.store'), [
        'title' => 'Test',
        'type' => DiscussionType::Internal->value,
        'discussionable_type' => 'App\\Models\\Malicious',
        'discussionable_id' => 1,
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
    ]);

    expect(Discussion::first()->type)->toBe(DiscussionType::PortalMessage);
});
