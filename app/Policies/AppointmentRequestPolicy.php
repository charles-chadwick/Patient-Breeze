<?php

namespace App\Policies;

use App\Models\AppointmentRequest;
use App\Models\User;

class AppointmentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_appointments');
    }

    /**
     * Reviewing (approving/declining) a request creates an appointment, so it
     * requires the same authority as creating one.
     */
    public function review(User $user, AppointmentRequest $appointmentRequest): bool
    {
        return $user->can('create_appointments');
    }
}
