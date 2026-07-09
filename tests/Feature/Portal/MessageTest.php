<?php

use App\Enums\DiscussionType;
use App\Enums\SettingKey;
use App\Enums\ToggleValue;
use App\Events\PortalNotificationCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('creates a discussion + post when a patient sends a portal message', function (): void {
    Event::fake([PortalNotificationCreated::class]);

    $patient = Patient::factory()->create();

    $this->actingAs($patient, 'portal')
        ->post(route('portal.messages.store'), [
            'title' => 'Refill request',
            'content' => 'Could you refill my prescription?',
        ])
        ->assertRedirect(route('portal.messages.index'));

    $discussion = Discussion::query()->first();
    expect($discussion)->not->toBeNull()
        ->and($discussion->type)->toBe(DiscussionType::PortalMessage)
        ->and($discussion->title)->toBe('Refill request')
        ->and($discussion->discussionable_type)->toBe(Patient::class)
        ->and($discussion->discussionable_id)->toBe($patient->id);

    $post = DiscussionPost::query()->first();
    expect($post->patient_id)->toBe($patient->id)
        ->and($post->user_id)->toBeNull();

    $notification = PortalNotification::query()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->notifiable_type)->toBe(DiscussionPost::class)
        ->and($notification->notifiable_id)->toBe($post->id)
        ->and($notification->patient_id)->toBe($patient->id)
        ->and($notification->url)->toContain(route('patients.show', $patient))
        ->and($notification->url)->toContain('tab=discussions')
        ->and($notification->url)->toContain("discussion={$discussion->id}");

    Event::assertDispatched(PortalNotificationCreated::class);
});

it('directs a portal message to opted-in staff as participants', function (): void {
    Event::fake([PortalNotificationCreated::class]);

    $patient = Patient::factory()->create();
    $recipient = User::factory()->create();
    $recipient->setSetting(SettingKey::ReceivesPortalMessages, ToggleValue::Enabled->value);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.messages.store'), [
            'content' => 'Please call me back.',
            'recipient_ids' => [$recipient->id],
        ])
        ->assertRedirect(route('portal.messages.index'));

    $discussion = Discussion::query()->firstOrFail();

    expect($discussion->participants()
        ->where('participantable_type', User::class)
        ->where('participantable_id', $recipient->id)
        ->where('is_initiator', false)
        ->exists())->toBeTrue();
});

it('sends a personal notification to each directed recipient only', function (): void {
    $patient = Patient::factory()->create();

    $recipient = User::factory()->create();
    $recipient->setSetting(SettingKey::ReceivesPortalMessages, ToggleValue::Enabled->value);

    $bystander = User::factory()->create();
    $bystander->setSetting(SettingKey::ReceivesPortalMessages, ToggleValue::Enabled->value);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.messages.store'), [
            'content' => 'Please call me back.',
            'recipient_ids' => [$recipient->id],
        ])
        ->assertRedirect(route('portal.messages.index'));

    expect($recipient->fresh()->notifications()->count())->toBe(1)
        ->and($bystander->fresh()->notifications()->count())->toBe(0);

    $notification = $recipient->fresh()->notifications()->first();
    expect($notification->read_at)->toBeNull()
        ->and($notification->data['discussion_id'])->toBe(Discussion::query()->value('id'));
});

it('only returns opted-in staff from the recipient search', function (): void {
    $patient = Patient::factory()->create();

    $opted_in = User::factory()->create(['last_name' => 'Zeta']);
    $opted_in->setSetting(SettingKey::ReceivesPortalMessages, ToggleValue::Enabled->value);

    $opted_out = User::factory()->create(['last_name' => 'Zeta']);

    $response = $this->actingAs($patient, 'portal')
        ->getJson(route('portal.messages.recipients.search', ['search' => 'Zeta']))
        ->assertOk();

    $ids = collect($response->json('users'))->pluck('id');

    expect($ids)->toContain($opted_in->id)
        ->and($ids)->not->toContain($opted_out->id);
});

it('rejects a recipient who has not opted in', function (): void {
    $patient = Patient::factory()->create();
    $opted_out = User::factory()->create();

    $this->actingAs($patient, 'portal')
        ->post(route('portal.messages.store'), [
            'content' => 'Hello there.',
            'recipient_ids' => [$opted_out->id],
        ])
        ->assertSessionHasErrors('recipient_ids.0');

    expect(Discussion::query()->count())->toBe(0);
});

it('lets a patient reply to their own discussion but not to another patient\'s', function (): void {
    Event::fake([PortalNotificationCreated::class]);

    $patient = Patient::factory()->create();
    $other = Patient::factory()->create();

    $discussion = $patient->discussions()->create([
        'type' => DiscussionType::PortalMessage,
        'title' => 'Portal Message',
        'status' => 'Open',
    ]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.messages.reply', $discussion), ['content' => 'Thanks!'])
        ->assertRedirect(route('portal.messages.index'));

    expect($discussion->posts()->count())->toBe(1);

    $this->actingAs($other, 'portal')
        ->post(route('portal.messages.reply', $discussion), ['content' => 'sneaky'])
        ->assertForbidden();
});

it('lists portal-message threads on the messages index', function (): void {
    $patient = Patient::factory()->create();
    $patient->discussions()->create([
        'type' => DiscussionType::PortalMessage,
        'title' => 'A',
        'status' => 'Open',
    ]);
    $patient->discussions()->create([
        'type' => DiscussionType::PortalMessage,
        'title' => 'B',
        'status' => 'Open',
    ]);
    $patient->discussions()->create([
        'type' => DiscussionType::General,
        'title' => 'Should be excluded',
        'status' => 'Open',
    ]);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.messages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Messages/Index')
            ->has('threads', 2)
        );
});

it('broadcasts on the portal-queue private channel', function (): void {
    $patient = Patient::factory()->create();
    $notification = PortalNotification::factory()->create(['patient_id' => $patient->id]);

    $channels = (new PortalNotificationCreated($notification))->broadcastOn();

    expect($channels[0]->name)->toBe('private-portal-queue');
});
