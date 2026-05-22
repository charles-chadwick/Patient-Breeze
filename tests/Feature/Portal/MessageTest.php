<?php

use App\Enums\DiscussionType;
use App\Events\PortalNotificationCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\PortalNotification;
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
