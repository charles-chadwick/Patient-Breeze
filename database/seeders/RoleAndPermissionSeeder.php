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

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (UserRole::cases() as $userRole) {
            $role = Role::findOrCreate($userRole->value);
            $role->syncPermissions($this->permissionsFor($userRole));
        }
    }

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'view_patients',
            'create_patients',
            'update_patients',
            'delete_patients',
            'view_users',
            'create_users',
            'update_users',
            'delete_users',
        ];
    }

    /**
     * @return list<string>
     */
    private function permissionsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::SuperAdmin => $this->permissions(),
            UserRole::Doctor => [
                'view_patients', 'create_patients', 'update_patients',
            ],
            UserRole::Nurse, UserRole::MedicalAssistant => [
                'view_patients', 'update_patients',
            ],
            UserRole::Staff => [
                'view_patients',
            ],
            UserRole::Patient => [],
        };
    }
}
