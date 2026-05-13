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
        $admin = User::factory()
            ->create([
                'prefix' => '',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'suffix' => '',
                'email' => 'admin@example.com',
                'created_at' => '2020-01-01 00:00:00',
            ]);

        $admin->assignRole(UserRole::SuperAdmin->value);

        foreach ($this->readCsv(database_path('data/dummy_users.csv')) as $data) {
            $role = fake()->randomElement(UserRole::cases());

            $user = User::factory()
                ->create([
                    'prefix' => $this->prefixForRole($role),
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? '',
                    'last_name' => $data['last_name'],
                    'suffix' => $this->suffixForRole($role),
                    'email' => $this->uniqueEmailFor($data['first_name'], $data['last_name']),
                    'created_at' => fake()->dateTimeBetween($admin->created_at, '-1 year'),
                ]);

            $user->assignRole($role->value);
        }
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
