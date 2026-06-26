<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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

        foreach ($this->readCsv(database_path('data/patients.csv')) as $data) {
            $createdAt = $data['created_at'];
            $user = $this->causerFor($users, $createdAt);

            $patient = Patient::factory()->create([
                'first_name' => $data['first_name'],
                'middle_name' => '',
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->attachAvatar($patient, 'patients', $data['avatar']);

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

    /**
     * Attribute the patient's creation to a user who already existed at that
     * time, falling back to any user when none predate the patient.
     *
     * @param  Collection<int, User>  $users
     */
    private function causerFor(Collection $users, string $createdAt): User
    {
        $eligible = $users->filter(fn (User $user) => $user->created_at <= $createdAt);

        return $eligible->isNotEmpty() ? $eligible->random() : $users->random();
    }
}
