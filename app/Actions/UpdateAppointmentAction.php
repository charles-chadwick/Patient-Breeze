<?php

namespace App\Actions;

use App\Enums\AppointmentRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentAction
{
    public function __construct(private AppointmentConflictService $conflictService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Appointment $appointment, array $validated): Appointment
    {
        $userIds = array_column($validated['staff'], 'user_id');

        $conflicts = $this->conflictService->findConflicts(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $userIds,
            $appointment->id,
        );

        if ($conflicts->isNotEmpty()) {
            $names = $conflicts->map(fn (User $u) => "{$u->first_name} {$u->last_name}")->join(', ');
            throw ValidationException::withMessages([
                'staff' => "The following staff have conflicting appointments: {$names}.",
            ]);
        }

        DB::transaction(function () use ($appointment, $validated) {
            $appointment->update([
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => $validated['status'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $appointment->users()->detach();

            $users = User::findMany(array_column($validated['staff'], 'user_id'))->keyBy('id');

            foreach ($validated['staff'] as $entry) {
                $appointment->attachProvider($users[$entry['user_id']], AppointmentRole::from($entry['role']));
            }
        });

        return $appointment->fresh();
    }
}
