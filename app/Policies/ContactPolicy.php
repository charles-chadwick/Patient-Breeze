<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_contacts');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view_contacts');
    }

    public function create(User $user): bool
    {
        return $user->can('create_contacts');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('update_contacts');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('delete_contacts');
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('delete_contacts');
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
