<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Discussion;
use App\Models\User;

class DiscussionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_discussions');
    }

    public function view(User $user, Discussion $discussion): bool
    {
        return $user->can('view_discussions');
    }

    public function create(User $user): bool
    {
        return $user->can('create_discussions');
    }

    public function update(User $user, Discussion $discussion): bool
    {
        return $user->can('update_discussions');
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        return $user->can('delete_discussions');
    }

    public function restore(User $user, Discussion $discussion): bool
    {
        return $user->can('delete_discussions');
    }

    public function forceDelete(User $user, Discussion $discussion): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
