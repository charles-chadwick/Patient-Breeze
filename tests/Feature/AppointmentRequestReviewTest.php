<?php

use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->reviewer = User::factory()->withRole(UserRole::Staff)->create();
    $this->actingAs($this->reviewer);
});

it('shows pending appointment requests in the portal queue', function (): void {
    $request = AppointmentRequest::factory()->pending()->create();

    $this->get(route('portal-queue.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PortalQueue/Index')
            ->has('appointment_requests', 1)
            ->where('appointment_requests.0.id', $request->id)
        );
});

it('approves a request and creates a confirmed appointment with the provider', function (): void {
    $patient = Patient::factory()->create();
    $provider = User::factory()->create();

    $request = AppointmentRequest::factory()
        ->pending()
        ->forPatient($patient)
        ->forProvider($provider)
        ->create([
            'date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

    $this->post(route('portal-queue.appointment-requests.approve', $request))
        ->assertRedirect()
        ->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe(AppointmentRequestStatus::Approved)
        ->and($request->reviewed_by)->toBe($this->reviewer->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($request->appointment_id)->not->toBeNull();

    $appointment = Appointment::find($request->appointment_id);
    expect($appointment)->not->toBeNull()
        ->and($appointment->patient_id)->toBe($patient->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->reason)->toBe($request->reason)
        ->and($appointment->primaryProvider()->id)->toBe($provider->id);
});

it('does not approve when the provider became busy since the request', function (): void {
    $provider = User::factory()->create();
    $date = now()->addWeek()->toDateString();

    $request = AppointmentRequest::factory()
        ->pending()
        ->forProvider($provider)
        ->create(['date' => $date, 'start_time' => '09:00:00', 'end_time' => '10:00:00']);

    Appointment::factory()
        ->forDate($date)
        ->withProvider($provider)
        ->create(['start_time' => '09:00', 'end_time' => '10:00']);

    $this->post(route('portal-queue.appointment-requests.approve', $request))
        ->assertSessionHasErrors('appointment_request');

    $request->refresh();
    expect($request->status)->toBe(AppointmentRequestStatus::Pending)
        ->and($request->appointment_id)->toBeNull();

    // Only the pre-existing conflicting appointment exists.
    expect(Appointment::count())->toBe(1);
});

it('declines a request without creating an appointment', function (): void {
    $request = AppointmentRequest::factory()->pending()->create();

    $this->post(route('portal-queue.appointment-requests.decline', $request))
        ->assertRedirect()
        ->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe(AppointmentRequestStatus::Declined)
        ->and($request->reviewed_by)->toBe($this->reviewer->id)
        ->and($request->appointment_id)->toBeNull();

    expect(Appointment::count())->toBe(0);
});

it('cannot re-review an already reviewed request', function (): void {
    $request = AppointmentRequest::factory()->declined()->create();

    $this->post(route('portal-queue.appointment-requests.approve', $request))
        ->assertStatus(422);
});

it('forbids staff without appointment permissions from approving', function (): void {
    $this->actingAs(User::factory()->create());

    $request = AppointmentRequest::factory()->pending()->create();

    $this->post(route('portal-queue.appointment-requests.approve', $request))
        ->assertForbidden();

    expect($request->fresh()->status)->toBe(AppointmentRequestStatus::Pending);
});
