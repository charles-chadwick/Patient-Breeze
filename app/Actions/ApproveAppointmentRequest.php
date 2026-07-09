<?php

namespace App\Actions;

use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveAppointmentRequest
{
    public function __construct(
        private BookAppointmentAction $bookAction,
        private AppointmentConflictService $conflictService,
    ) {}

    /**
     * Approve a pending request: confirm the provider is still free, create the
     * confirmed appointment with them as primary provider, and link it back.
     */
    public function execute(AppointmentRequest $request, User $reviewer): Appointment
    {
        $this->guardAgainstConflict($request);

        return DB::transaction(function () use ($request, $reviewer) {
            $appointment = $this->bookAction->execute($request->patient, [
                'date' => $request->date->toDateString(),
                'start_time' => substr($request->start_time, 0, 5),
                'end_time' => substr($request->end_time, 0, 5),
                'status' => AppointmentStatus::Confirmed->value,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'staff' => [
                    ['user_id' => $request->user_id, 'role' => AppointmentRole::Primary->value],
                ],
            ]);

            $request->update([
                'status' => AppointmentRequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'appointment_id' => $appointment->id,
            ]);

            return $appointment;
        });
    }

    private function guardAgainstConflict(AppointmentRequest $request): void
    {
        $conflicts = $this->conflictService->findConflicts(
            $request->date->toDateString(),
            substr($request->start_time, 0, 5),
            substr($request->end_time, 0, 5),
            [$request->user_id],
        );

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'appointment_request' => __('flash.appointment_requests.conflict'),
            ]);
        }
    }
}
