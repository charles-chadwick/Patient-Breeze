<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Throwable;

class UserSeeder extends Seeder
{
    use ReadsCsvData;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        foreach ($this->readCsv(database_path('data/users.csv')) as $data) {
            $role = $this->roleFor($data['role']);

            $user = User::factory()
                ->create([
                    'prefix' => $this->prefixForRole($role),
                    'first_name' => $data['first_name'],
                    'middle_name' => '',
                    'last_name' => $data['last_name'],
                    'suffix' => $this->suffixForRole($role),
                    'email' => $data['email'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['created_at'],
                ]);

            $user->assignRole($role->value);

            $this->attachAvatar($user, 'users', $data['avatar']);
        }
    }

    /**
     * Map a CRM role onto this application's UserRole. CRM "Admin" accounts
     * have no clinical equivalent, so they are given a random clinical role.
     */
    private function roleFor(string $crmRole): UserRole
    {
        return match ($crmRole) {
            'Super Admin' => UserRole::SuperAdmin,
            'Staff' => UserRole::Staff,
            default => fake()->randomElement([
                UserRole::Doctor,
                UserRole::Nurse,
                UserRole::MedicalAssistant,
            ]),
        };
    }

    private function prefixForRole(UserRole $role): string
    {
        return match ($role) {
            UserRole::Doctor => 'Dr.',
            default => '',
        };
    }

    private function suffixForRole(UserRole $role): string
    {
        return match ($role) {
            UserRole::Doctor => fake()->randomElement([
                'MD',
                'DO',
            ]),
            UserRole::Nurse => fake()->randomElement([
                'RN',
                'BSN',
                'NP',
            ]),
            UserRole::MedicalAssistant => 'CMA',
            default => '',
        };
    }
}
