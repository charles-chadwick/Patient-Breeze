<?php

namespace Database\Factories;

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(GenderAtBirth::cases());

        return [
            'user_id' => User::factory()->state([
                'prefix' => $this->prefixForGender($gender),
            ]),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'gender_at_birth' => $gender,
            'gender_identity' => fake()->randomElement(GenderIdentity::cases()),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Patient $patient): void {
            Role::findOrCreate(UserRole::Patient->value);
            $patient->user->assignRole(UserRole::Patient->value);
        });
    }

    /**
     * @param  array<string, mixed>  $userState
     */
    public function withUserState(array $userState): static
    {
        return $this->state(function (array $attributes) use ($userState) {
            return [
                'user_id' => User::factory()->state(array_merge([
                    'prefix' => $this->prefixForGender($attributes['gender_at_birth']),
                ], $userState)),
            ];
        });
    }

    private function prefixForGender(GenderAtBirth $gender): string
    {
        return match ($gender) {
            GenderAtBirth::Male => 'Mr.',
            GenderAtBirth::Female => 'Ms.',
            GenderAtBirth::Unknown => '',
        };
    }
}
