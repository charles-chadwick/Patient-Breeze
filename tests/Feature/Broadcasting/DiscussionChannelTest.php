<?php

use App\Broadcasting\DiscussionChannel;
use App\Models\Discussion;
use App\Models\DiscussionParticipant;
use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    $this->channel = new DiscussionChannel;
    $this->discussion = Discussion::factory()->for(Patient::factory(), 'discussionable')->create();
});

it('authorizes a staff user who participates in the discussion', function (): void {
    $user = User::factory()->create();
    DiscussionParticipant::factory()->for($this->discussion)->create([
        'participantable_id' => $user->id,
        'participantable_type' => User::class,
    ]);

    expect($this->channel->join($user, $this->discussion->id))->toBeTrue();
});

it('rejects a staff user who does not participate in the discussion', function (): void {
    $user = User::factory()->create();

    expect($this->channel->join($user, $this->discussion->id))->toBeFalse();
});

it('rejects a staff user who participates in a different discussion', function (): void {
    $user = User::factory()->create();
    $otherDiscussion = Discussion::factory()->for(Patient::factory(), 'discussionable')->create();
    DiscussionParticipant::factory()->for($otherDiscussion)->create([
        'participantable_id' => $user->id,
        'participantable_type' => User::class,
    ]);

    expect($this->channel->join($user, $this->discussion->id))->toBeFalse();
});

it('authorizes a patient who participates in the discussion', function (): void {
    $patient = Patient::factory()->create();
    DiscussionParticipant::factory()->for($this->discussion)->forPatient($patient)->create();

    expect($this->channel->join($patient, $this->discussion->id))->toBeTrue();
});

it('rejects a patient who does not participate in the discussion', function (): void {
    $patient = Patient::factory()->create();

    expect($this->channel->join($patient, $this->discussion->id))->toBeFalse();
});

it('ignores soft-deleted participant records', function (): void {
    $user = User::factory()->create();
    $participant = DiscussionParticipant::factory()->for($this->discussion)->create([
        'participantable_id' => $user->id,
        'participantable_type' => User::class,
    ]);
    $participant->delete();

    expect($this->channel->join($user, $this->discussion->id))->toBeFalse();
});
