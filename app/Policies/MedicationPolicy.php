<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_medications');
    }

    public function view(User $user, Medication $medication): bool
    {
        return $user->can('view_medications');
    }

    public function create(User $user): bool
    {
        return $user->can('create_medications');
    }

    public function update(User $user, Medication $medication): bool
    {
        return $user->can('update_medications');
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $user->can('delete_medications');
    }

    public function restore(User $user, Medication $medication): bool
    {
        return $user->can('delete_medications');
    }

    public function forceDelete(User $user, Medication $medication): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
