<?php

namespace Database\Factories;

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(GenderAtBirth::cases());

        return [
            'prefix' => $this->prefixForGender($gender),
            'first_name' => fake()->firstName(),
            'middle_name' => '',
            'last_name' => fake()->lastName(),
            'suffix' => '',
            'email' => fake()->unique()->safeEmail(),
            'mrn' => 'MRN-'.str_pad((string) fake()->unique()->numberBetween(1, 9999999), 7, '0', STR_PAD_LEFT),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'gender_at_birth' => $gender,
            'gender_identity' => fake()->randomElement(GenderIdentity::cases()),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        ];
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
