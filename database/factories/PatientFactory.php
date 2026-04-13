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
        $gender_at_birth = $this->faker->randomElement(GenderAtBirth::cases());

        $prefix = match ($gender_at_birth) {
            GenderAtBirth::Male => 'Mr.',
            GenderAtBirth::Female => $this->faker->randomElement(['Mrs.', 'Ms.']),
            GenderAtBirth::Unknown => $this->faker->randomElement(['Mr.', 'Mrs.', 'Ms.']),
        };

        $created_at = fake()->dateTimeBetween('2021-01-01', 'yesterday');

        return [
            'prefix' => $prefix,
            'date_of_birth' => fake()->dateTimeBetween('-100 years', '-18 months'),
            'gender_at_birth' => $gender_at_birth,
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'user_id' => User::factory(),
        ];
    }
}
