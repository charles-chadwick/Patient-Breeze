<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Throwable;

class PatientSeeder extends Seeder
{
    use ReadsCsvData;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        foreach ($this->readCsv(database_path('data/dummy_patients.csv')) as $data) {
            Patient::factory()->create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? '',
                'last_name' => $data['last_name'],
                'email' => $this->uniqueEmailFor($data['first_name'], $data['last_name']),
            ]);
        }
    }
}
