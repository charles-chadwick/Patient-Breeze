<?php

namespace Database\Factories;

use App\Enums\GenderAtBirth;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(GenderAtBirth::cases());
        $prefix = match ($gender) {
            GenderAtBirth::Male => 'Mr.',
            GenderAtBirth::Female => 'Ms.',
            GenderAtBirth::Unknown => '',
        };

        return [
            'user_id' => User::factory()->state([
                'is_patient' => true,
                'prefix' => $prefix,
            ]),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'gender_at_birth' => $gender,
            'gender_identity' => fake()->randomElement(['Male', 'Female', 'Non-binary', 'Prefer not to say']),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ];
    }

    /**
     * @param  array<string, mixed>  $userState
     */
    public function withUserState(array $userState): static
    {
        return $this->state(function (array $attributes) use ($userState) {
            $gender = $attributes['gender_at_birth'];
            $prefix = match ($gender) {
                GenderAtBirth::Male => 'Mr.',
                GenderAtBirth::Female => 'Ms.',
                GenderAtBirth::Unknown => '',
            };

            return [
                'user_id' => User::factory()->state(array_merge([
                    'is_patient' => true,
                    'prefix' => $prefix,
                ], $userState)),
            ];
        });
    }
}
