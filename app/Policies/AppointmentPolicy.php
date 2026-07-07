<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_appointments');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('view_appointments');
    }

    public function create(User $user): bool
    {
        return $user->can('create_appointments');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('update_appointments');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('delete_appointments');
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->can('delete_appointments');
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
