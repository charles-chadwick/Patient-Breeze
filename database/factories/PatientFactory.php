<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition() : array
    {
        return [
            'status'          => $this->faker->word(),
            
            'prefix'          => $this->faker->word(),
            'first_name'      => $this->faker->firstName(),
            'middle_name'     => $this->faker->firstName(),
            'last_name'       => $this->faker->lastName(),
            'suffix'          => $this->faker->word(),
            'dob'             => Carbon::now(),
            'gender'          => $this->faker->randomElement(Gender::cases()),
            'gender_identity' => $this->faker->word(),
            'email'           => $this->faker->unique()
                ->safeEmail(),
            'password'        => bcrypt('password'),
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now(),
        ];
    }
}
