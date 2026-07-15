<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Allergen;
use App\Models\User;

class AllergenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_allergens');
    }

    public function view(User $user, Allergen $allergen): bool
    {
        return $user->can('view_allergens');
    }

    public function create(User $user): bool
    {
        return $user->can('create_allergens');
    }

    public function update(User $user, Allergen $allergen): bool
    {
        return $user->can('update_allergens');
    }

    public function delete(User $user, Allergen $allergen): bool
    {
        return $user->can('delete_allergens');
    }

    public function restore(User $user, Allergen $allergen): bool
    {
        return $user->can('delete_allergens');
    }

    public function forceDelete(User $user, Allergen $allergen): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
