<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AppointmentConflictService
{
    /**
     * @param  array<int>  $userIds
     * @return Collection<int, User>
     */
    public function findConflicts(
        string $date,
        string $start_time,
        string $end_time,
        array $userIds,
        ?int $excludeAppointmentId = null
    ): Collection {
        return User::whereIn('id', $userIds)
            ->whereHas('appointments', function ($query) use ($date, $start_time, $end_time, $excludeAppointmentId) {
                $query->whereDate('date', $date)
                    ->where('start_time', '<', $end_time)
                    ->where('end_time', '>', $start_time)
                    ->when($excludeAppointmentId, fn ($q) => $q->where('appointments.id', '!=', $excludeAppointmentId));
            })
            ->get();
    }
}
