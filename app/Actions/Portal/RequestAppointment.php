<?php

namespace App\Actions\Portal;

use App\Enums\AppointmentRequestStatus;
use App\Events\PortalNotificationCreated;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class RequestAppointment
{
    use AsAction;

    public function __construct(private AppointmentConflictService $conflictService) {}

    /**
     * Create a pending appointment request for the patient after confirming the
     * requested provider is free at the desired slot, then queue it for staff.
     *
     * @param  array{user_id: int, date: string, start_time: string, end_time: string, reason: string, notes?: ?string}  $data
     */
    public function handle(Patient $patient, array $data): AppointmentRequest
    {
        $this->guardAgainstConflict($data);

        return DB::transaction(function () use ($patient, $data) {
            $request = AppointmentRequest::create([
                'patient_id' => $patient->id,
                'user_id' => $data['user_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'status' => AppointmentRequestStatus::Pending,
            ]);

            $notification = PortalNotification::create([
                'type' => 'portal.appointment.requested',
                'notifiable_type' => AppointmentRequest::class,
                'notifiable_id' => $request->id,
                'patient_id' => $patient->id,
                'title' => "{$patient->first_name} {$patient->last_name} requested an appointment",
                'body' => str($data['reason'])->limit(140),
                'url' => route('portal-queue.index'),
            ]);

            PortalNotificationCreated::dispatch($notification);

            return $request;
        });
    }

    /**
     * @param  array{user_id: int, date: string, start_time: string, end_time: string}  $data
     */
    private function guardAgainstConflict(array $data): void
    {
        $conflicts = $this->conflictService->findConflicts(
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            [$data['user_id']],
        );

        if ($conflicts->isNotEmpty()) {
            /** @var User $provider */
            $provider = $conflicts->first();

            throw ValidationException::withMessages([
                'user_id' => __('portal.appointments.conflict', [
                    'name' => "{$provider->first_name} {$provider->last_name}",
                ]),
            ]);
        }
    }
}
