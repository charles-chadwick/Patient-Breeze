<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Diagnosis;
use App\Models\User;

class DiagnosisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_diagnoses');
    }

    public function view(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('view_diagnoses');
    }

    public function create(User $user): bool
    {
        return $user->can('create_diagnoses');
    }

    public function update(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('update_diagnoses');
    }

    public function delete(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('delete_diagnoses');
    }

    public function restore(User $user, Diagnosis $diagnosis): bool
    {
        return $user->can('delete_diagnoses');
    }

    public function forceDelete(User $user, Diagnosis $diagnosis): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
