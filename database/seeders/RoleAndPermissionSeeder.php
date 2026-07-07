<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (UserRole::allPermissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (UserRole::cases() as $userRole) {
            $role = Role::findOrCreate($userRole->value);
            $role->syncPermissions($userRole->permissions());
        }
    }
}
