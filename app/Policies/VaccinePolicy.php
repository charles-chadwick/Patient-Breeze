<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vaccine;

class VaccinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_vaccines');
    }

    public function view(User $user, Vaccine $vaccine): bool
    {
        return $user->can('view_vaccines');
    }

    public function create(User $user): bool
    {
        return $user->can('create_vaccines');
    }

    public function update(User $user, Vaccine $vaccine): bool
    {
        return $user->can('update_vaccines');
    }

    public function delete(User $user, Vaccine $vaccine): bool
    {
        return $user->can('delete_vaccines');
    }

    public function restore(User $user, Vaccine $vaccine): bool
    {
        return $user->can('delete_vaccines');
    }

    public function forceDelete(User $user, Vaccine $vaccine): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
