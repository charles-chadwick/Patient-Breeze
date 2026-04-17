<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Throwable;

class PatientSeeder extends Seeder
{
    /**
     * @throws Throwable
     */
    public function run(): void
    {
        foreach ($this->readCsv(database_path('data/dummy_patients.csv')) as $data) {
            Patient::factory()
                ->withUserState([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? '',
                    'last_name' => $data['last_name'],
                    'email' => $this->uniqueEmailFor($data['first_name'], $data['last_name']),
                ])
                ->create();
        }
    }

    private function uniqueEmailFor(string $firstName, string $lastName): string
    {
        $base = strtolower($firstName.'.'.$lastName);
        $email = $base.'@example.com';
        $suffix = 1;

        while (User::withTrashed()->where('email', $email)->exists()) {
            $email = $base.(++$suffix).'@example.com';
        }

        return $email;
    }

    /**
     * @return list<array<string, string>>
     *
     * @throws Throwable
     */
    private function readCsv(string $path): array
    {
        throw_if(! file_exists($path), new \RuntimeException("CSV file not found: {$path}"));

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        return array_values(array_filter(
            array_map(fn (array $row) => count($row) >= 3 ? array_combine($header, $row) : null, $rows),
            fn (?array $row) => $row !== null,
        ));
    }
}
