<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition() : array
    {
        return [
            'status'          => $this->faker->randomElement(PatientStatus::cases()),
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
            'created_by_id'   => User::inRandomOrder()
                ->first()->id,
        ];
    }
}
