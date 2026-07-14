<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LabOrder;
use App\Models\User;

class LabOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_lab_orders');
    }

    public function view(User $user, LabOrder $labOrder): bool
    {
        return $user->can('view_lab_orders');
    }

    public function create(User $user): bool
    {
        return $user->can('create_lab_orders');
    }

    public function update(User $user, LabOrder $labOrder): bool
    {
        return $user->can('update_lab_orders');
    }

    public function delete(User $user, LabOrder $labOrder): bool
    {
        return $user->can('delete_lab_orders');
    }

    public function restore(User $user, LabOrder $labOrder): bool
    {
        return $user->can('delete_lab_orders');
    }

    public function forceDelete(User $user, LabOrder $labOrder): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
