<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_notes');
    }

    public function view(User $user, Note $note): bool
    {
        return $user->can('view_notes');
    }

    public function create(User $user): bool
    {
        return $user->can('create_notes');
    }

    public function update(User $user, Note $note): bool
    {
        return $user->can('update_notes');
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->can('delete_notes');
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->can('delete_notes');
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
