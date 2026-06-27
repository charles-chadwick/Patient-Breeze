<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AppointmentConflictService
{
    /**
     * @param  array<int>  $user_ids
     * @return Collection<int, User>
     */
    public function findConflicts(
        string $date,
        string $start_time,
        string $end_time,
        array $user_ids,
        ?int $exclude_appointment_id = null
    ): Collection {
        return User::whereIn('id', $user_ids)
            ->whereHas('appointments', function ($query) use ($date, $start_time, $end_time, $exclude_appointment_id) {
                $query->whereDate('date', $date)
                    ->where('start_time', '<', $end_time)
                    ->where('end_time', '>', $start_time)
                    ->when($exclude_appointment_id, fn ($query) => $query->where('appointments.id', '!=', $exclude_appointment_id));
            })
            ->get();
    }
}
