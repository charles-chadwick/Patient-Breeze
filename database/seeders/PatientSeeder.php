<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->readCsv(database_path('data/dummy_patients.csv')) as $data) {
            Patient::factory()
                ->withUserState([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? '',
                    'last_name' => $data['last_name'],
                    'email' => strtolower($data['first_name'].'.'.$data['last_name']).'@example.com',
                ])
                ->create();
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
        );
    }
}
