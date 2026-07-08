<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use ReadsCsvData;

    /**
     * The total number of users to seed, including the Super Admins.
     */
    private const int USER_COUNT = 55;

    /**
     * How many of the non-admin users receive a clinical role. The remainder
     * are Staff.
     */
    private const int CLINICAL_COUNT = 11;

    /**
     * The consistent Super Admins, keyed to their Rick and Morty character.
     * These are always created first so the same accounts exist every seed.
     *
     * @var array<int, array{id: int, first_name: string, last_name: string}>
     */
    private array $super_admins = [
        ['id' => 328, 'first_name' => 'Slow', 'last_name' => 'Rick'],
        ['id' => 103, 'first_name' => 'Doofus', 'last_name' => 'Rick'],
        ['id' => 347, 'first_name' => 'President', 'last_name' => 'Curtis'],
        ['id' => 128, 'first_name' => "Frankenstein's", 'last_name' => 'Monster'],
        ['id' => 280, 'first_name' => 'Reverse', 'last_name' => 'Giraffe'],
    ];

    /**
     * The next sequential user avatar (1.jpeg .. 55.jpeg) to attach.
     */
    private int $avatar_number = 1;

    public function run(): void
    {
        RickAndMortyCharacters::reset();

        $this->seedSuperAdmins();
        $this->seedStaff();
    }

    /**
     * Create the consistent Super Admins from their hard-coded characters.
     */
    private function seedSuperAdmins(): void
    {
        foreach ($this->super_admins as $character) {
            $user = User::factory()->create([
                'prefix' => '',
                'first_name' => $character['first_name'],
                'middle_name' => '',
                'last_name' => $character['last_name'],
                'suffix' => '',
                'email' => $this->emailFor("{$character['first_name']} {$character['last_name']}"),
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

            $user->assignRole(UserRole::SuperAdmin->value);

            RickAndMortyCharacters::markUsed($character['id']);
            $this->attachSequentialAvatar($user);
        }
    }

    /**
     * Create the remaining clinical and staff users from the character pool.
     */
    private function seedStaff(): void
    {
        $count = self::USER_COUNT - count($this->super_admins);

        RickAndMortyCharacters::remaining()
            ->take($count)
            ->each(function (array $character, int $index): void {
                $role = $index < self::CLINICAL_COUNT
                    ? fake()->randomElement([UserRole::Doctor, UserRole::Nurse, UserRole::MedicalAssistant])
                    : UserRole::Staff;

                $created_at = fake()->dateTimeBetween('-5 years', '-6 months');

                $user = User::factory()->create([
                    'prefix' => $this->prefixForRole($role),
                    'first_name' => $character['first_name'],
                    'middle_name' => '',
                    'last_name' => $character['last_name'],
                    'suffix' => $this->suffixForRole($role),
                    'email' => $this->emailFor("{$character['first_name']} {$character['last_name']}", $character['id']),
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);

                $user->assignRole($role->value);

                RickAndMortyCharacters::markUsed($character['id']);
                $this->attachSequentialAvatar($user);
            });
    }

    /**
     * Attach the next sequential avatar from database/data/avatars/users.
     */
    private function attachSequentialAvatar(User $user): void
    {
        $this->attachAvatar($user, 'users', "{$this->avatar_number}.jpeg");

        $this->avatar_number++;
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
