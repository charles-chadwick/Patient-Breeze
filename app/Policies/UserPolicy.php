<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_users');
    }

    public function view(User $user, User $model): bool
    {
        // Every user may view their own profile; viewing others requires the permission.
        return $user->id === $model->id || $user->can('view_users');
    }

    public function create(User $user): bool
    {
        return $user->can('create_users');
    }

    public function update(User $user, User $model): bool
    {
        if ($this->isProtectedSuperAdmin($user, $model)) {
            return false;
        }

        return $user->can('update_users');
    }

    public function delete(User $user, User $model): bool
    {
        if ($this->isProtectedSuperAdmin($user, $model)) {
            return false;
        }

        // Prevent users from deleting their own account.
        return $user->can('delete_users') && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('delete_users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }

    /**
     * A Super Admin account may only be edited or deleted by another Super
     * Admin. This is the sole restriction on a Doctor's otherwise full user
     * management.
     */
    private function isProtectedSuperAdmin(User $user, User $model): bool
    {
        return $model->hasRole(UserRole::SuperAdmin->value)
            && ! $user->hasRole(UserRole::SuperAdmin->value);
    }
}
