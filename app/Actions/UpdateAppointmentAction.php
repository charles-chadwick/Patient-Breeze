<?php

namespace App\Actions;

use App\Enums\AppointmentRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Enums\ActivityEvent;
use Throwable;

class UpdateAppointmentAction
{
    public function __construct(private AppointmentConflictService $conflictService) {}

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws Throwable
     */
    public function execute(Appointment $appointment, array $validated): Appointment
    {
        $user_ids = array_column($validated['staff'], 'user_id');

        $conflicts = $this->conflictService->findConflicts(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $user_ids,
            $appointment->id,
        );

        if ($conflicts->isNotEmpty()) {
            $names = $conflicts->map(fn (User $user) => "{$user->first_name} {$user->last_name}")->join(', ');
            throw ValidationException::withMessages([
                'staff' => "The following staff have conflicting appointments: {$names}.",
            ]);
        }

        $previous_providers = $this->describeProviders($appointment);

        DB::transaction(function () use ($appointment, $validated, $previous_providers) {
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

            $this->recordProviderChanges($appointment, $previous_providers);
        });

        return $appointment->fresh();
    }

    /**
     * Pivot changes leave no dirty attribute for the model's automatic activity
     * log to report, so record assigned-provider changes explicitly.
     */
    private function recordProviderChanges(Appointment $appointment, string $previous_providers): void
    {
        $current_providers = $this->describeProviders($appointment);

        if ($current_providers === $previous_providers) {
            return;
        }

        activity()
            ->performedOn($appointment)
            ->event(ActivityEvent::Updated)
            ->withProperties([
                'attributes' => ['providers' => $current_providers],
                'old' => ['providers' => $previous_providers],
            ])
            ->log(ActivityEvent::Updated->value);
    }

    /**
     * A stable, human-readable snapshot of an appointment's assigned providers
     * and their roles, used to diff the assignment across an update.
     */
    private function describeProviders(Appointment $appointment): string
    {
        return $appointment->users()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user): string => trim("{$user->first_name} {$user->last_name}")." ({$user->pivot->role})")
            ->join(', ');
    }
}
