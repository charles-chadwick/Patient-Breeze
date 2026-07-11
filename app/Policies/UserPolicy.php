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
        return $user->can('view_users');
    }

    public function create(User $user): bool
    {
        return $user->can('create_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update_users');
    }

    public function delete(User $user, User $model): bool
    {
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
}
