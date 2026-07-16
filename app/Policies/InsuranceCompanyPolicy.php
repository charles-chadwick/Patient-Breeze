<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InsuranceCompany;
use App\Models\User;

class InsuranceCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_insurance_companies');
    }

    public function view(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->can('view_insurance_companies');
    }

    public function create(User $user): bool
    {
        return $user->can('create_insurance_companies');
    }

    public function update(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->can('update_insurance_companies');
    }

    public function delete(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->can('delete_insurance_companies');
    }

    public function restore(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->can('delete_insurance_companies');
    }

    public function forceDelete(User $user, InsuranceCompany $insuranceCompany): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
