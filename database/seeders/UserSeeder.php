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
        $nonPatientRoles = array_filter(
            UserRole::cases(),
            fn (UserRole $role) => $role !== UserRole::Patient,
        );

        foreach ($this->readCsv(database_path('data/dummy_users.csv')) as $data) {
            $role = fake()->randomElement($nonPatientRoles);

            $user = User::factory()->create([
                'prefix' => $this->prefixForRole($role),
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? '',
                'last_name' => $data['last_name'],
                'suffix' => $this->suffixForRole($role),
                'email' => $this->uniqueEmailFor($data['first_name'], $data['last_name']),
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
            UserRole::Doctor => fake()->randomElement(['MD', 'DO']),
            UserRole::Nurse => fake()->randomElement(['RN', 'BSN', 'NP']),
            UserRole::MedicalAssistant => 'CMA',
            default => '',
        };
    }
}
