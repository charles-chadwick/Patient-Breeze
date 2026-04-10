<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
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
                'email' => strtolower($data['first_name'].'.'.$data['last_name']).'@example.com',
                'is_patient' => false,
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

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        return array_filter(
            array_map(fn (array $row) => count($row) >= 3 ? array_combine($header, $row) : null, $rows),
            fn ($row) => $row !== null
        );
    }
}
