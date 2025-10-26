<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition() : array
    {
        return [
            'start'         => Carbon::now(),
            'end'           => Carbon::now(),
            'status'        => $this->faker->randomElement(AppointmentStatus::cases()),
            'type'          => $this->faker->word(),
            'title'         => $this->faker->word(),
            'description'   => $this->faker->text(),
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
            'patient_id'    => Patient::inRandomOrder()
                ->first()->id,
            'created_by_id' => User::inRandomOrder()
                ->first()->id,
        ];
    }
}
