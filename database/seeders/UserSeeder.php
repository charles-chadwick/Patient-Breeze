<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->readCsv(database_path('data/dummy_users.csv')) as $data) {
            User::factory()->create([
                'prefix' => fake()->randomElement(['Dr.', '']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'suffix' => fake()->randomElement(['MD', 'PhD', '']),
                'email' => strtolower($data['first_name'].'.'.$data['last_name']).'@example.com',
                'is_patient' => false,
            ]);
        }
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
