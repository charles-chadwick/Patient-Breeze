<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    use ReadsCsvData;

    /**
     * The total number of patients to seed.
     */
    private const int PATIENT_COUNT = 519;

    /**
     * The next sequential patient avatar to attach. Patient avatars are
     * numbered directly after the user avatars (56.jpeg .. 574.jpeg).
     */
    private int $avatar_number = 56;

    public function run(): void
    {
        $users = User::select(['id', 'created_at'])->get();

        RickAndMortyCharacters::remaining()
            ->take(self::PATIENT_COUNT)
            ->each(function (array $character) use ($users): void {
                $full_name = "{$character['first_name']} {$character['last_name']}";
                $name = $this->splitName($full_name);
                $created_at = fake()->dateTimeBetween('-3 years', 'now');
                $user = $this->causerFor($users, $created_at);

                $patient = Patient::factory()->create([
                    'first_name' => $name['first_name'],
                    'middle_name' => $name['middle_name'],
                    'last_name' => $name['last_name'],
                    'email' => $this->emailFor($full_name, $character['id']),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);

                RickAndMortyCharacters::markUsed($character['id']);

                $this->attachAvatar($patient, 'patients', "{$this->avatar_number}.jpeg");
                $this->avatar_number++;

                $this->logCreation($user, $patient, $created_at);
            });
    }

    /**
     * Split a character's full name into first, middle, and last parts. When a
     * name has more than two words, every word between the first and last
     * becomes the middle name, so "Ants in my Eyes Johnson" yields first
     * "Ants", middle "in my Eyes", last "Johnson".
     *
     * @return array{first_name: string, middle_name: string, last_name: string}
     */
    private function splitName(string $name): array
    {
        $words = preg_split('/\s+/', trim($name));

        if (count($words) <= 2) {
            return [
                'first_name' => $words[0] ?? '',
                'middle_name' => '',
                'last_name' => $words[1] ?? '',
            ];
        }

        $first_name = array_shift($words);
        $last_name = array_pop($words);

        return [
            'first_name' => $first_name,
            'middle_name' => implode(' ', $words),
            'last_name' => $last_name,
        ];
    }

    /**
     * Attribute the patient's creation to a user who already existed at that
     * time, falling back to any user when none predate the patient.
     *
     * @param  Collection<int, User>  $users
     */
    private function causerFor(Collection $users, DateTimeInterface $created_at): User
    {
        $eligible = $users->filter(fn (User $user) => $user->created_at <= $created_at);

        return $eligible->isNotEmpty() ? $eligible->random() : $users->random();
    }

    /**
     * Record a backdated "created" activity entry attributed to the causer.
     */
    private function logCreation(User $user, Patient $patient, DateTimeInterface $created_at): void
    {
        $activity = activity()
            ->causedBy($user)
            ->performedOn($patient)
            ->event('created')
            ->withProperties(['attributes' => $patient->only($patient->getFillable())])
            ->log('created');

        $activity->created_at = $created_at;
        $activity->updated_at = $created_at;
        $activity->saveQuietly();
    }
}
