<?php

use App\Enums\AppointmentRequestStatus;
use App\Events\PortalNotificationCreated;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function requestPayload(User $provider, array $overrides = []): array
{
    return array_merge([
        'user_id' => $provider->id,
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
        'reason' => 'Annual physical exam',
        'notes' => 'Prefer the morning.',
    ], $overrides);
}

it('lets a patient request an appointment with a provider', function (): void {
    Event::fake([PortalNotificationCreated::class]);

    $patient = Patient::factory()->create();
    $provider = User::factory()->create();

    $this->actingAs($patient, 'portal')
        ->post(route('portal.appointment-requests.store'), requestPayload($provider))
        ->assertRedirect(route('portal.dashboard'))
        ->assertSessionHas('success');

    $request = AppointmentRequest::query()->first();
    expect($request)->not->toBeNull()
        ->and($request->patient_id)->toBe($patient->id)
        ->and($request->user_id)->toBe($provider->id)
        ->and($request->status)->toBe(AppointmentRequestStatus::Pending)
        ->and($request->appointment_id)->toBeNull();

    // Nothing is booked until staff approve it.
    expect(Appointment::count())->toBe(0);

    $notification = PortalNotification::query()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('portal.appointment.requested')
        ->and($notification->notifiable_type)->toBe(AppointmentRequest::class)
        ->and($notification->notifiable_id)->toBe($request->id)
        ->and($notification->patient_id)->toBe($patient->id);

    Event::assertDispatched(PortalNotificationCreated::class);
});

it('rejects a request when the provider already has an appointment at that time', function (): void {
    $patient = Patient::factory()->create();
    $provider = User::factory()->create();

    Appointment::factory()
        ->forDate(now()->addWeek()->toDateString())
        ->withProvider($provider)
        ->create(['start_time' => '09:00', 'end_time' => '10:00']);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.appointment-requests.store'), requestPayload($provider, [
            'start_time' => '09:30',
            'end_time' => '10:30',
        ]))
        ->assertSessionHasErrors('user_id');

    expect(AppointmentRequest::count())->toBe(0);
});

it('allows a request when the provider is busy at a non-overlapping time', function (): void {
    $patient = Patient::factory()->create();
    $provider = User::factory()->create();

    Appointment::factory()
        ->forDate(now()->addWeek()->toDateString())
        ->withProvider($provider)
        ->create(['start_time' => '09:00', 'end_time' => '10:00']);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.appointment-requests.store'), requestPayload($provider, [
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]))
        ->assertSessionHasNoErrors();

    expect(AppointmentRequest::count())->toBe(1);
});

it('validates the request fields', function (): void {
    $patient = Patient::factory()->create();
    $provider = User::factory()->create();

    $this->actingAs($patient, 'portal')
        ->post(route('portal.appointment-requests.store'), requestPayload($provider, [
            'date' => now()->subDay()->toDateString(),
            'end_time' => '08:00',
            'reason' => '',
        ]))
        ->assertSessionHasErrors(['date', 'end_time', 'reason']);
});

it('requires the provider to be a staff member', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($patient, 'portal')
        ->post(route('portal.appointment-requests.store'), requestPayload($provider = User::factory()->create(), [
            'user_id' => 999999,
        ]))
        ->assertSessionHasErrors('user_id');

    expect(AppointmentRequest::count())->toBe(0);
});

it('prevents guests from requesting appointments', function (): void {
    $provider = User::factory()->create();

    $this->post(route('portal.appointment-requests.store'), requestPayload($provider))
        ->assertRedirect(route('portal.login'));
});
