<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
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
        $users = User::select(['id', 'created_at'])->get();

        foreach ($this->readCsv(database_path('data/dummy_patients.csv')) as $data) {
            $user = $users->random();
            $createdAt = fake()->dateTimeBetween($user->created_at, '-1 month');

            $patient = Patient::factory()->create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? '',
                'last_name' => $data['last_name'],
                'email' => $this->uniqueEmailFor($data['first_name'], $data['last_name']),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $activity = activity()
                ->causedBy($user)
                ->performedOn($patient)
                ->event('created')
                ->withProperties(['attributes' => $patient->only($patient->getFillable())])
                ->log('created');

            $activity->created_at = $createdAt;
            $activity->updated_at = $createdAt;
            $activity->saveQuietly();
        }
    }
}
