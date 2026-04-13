<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $provider = User::inRandomOrder()->first() ?? User::factory()->create();
        $patient = User::where('id', '!=', $provider->id)->inRandomOrder()->first() ?? User::factory()->create();

        $start_hour = fake()->numberBetween(8, 16);
        $start_minute = fake()->randomElement([0, 30]);
        $start_time = sprintf('%02d:%02d:00', $start_hour, $start_minute);

        $duration_minutes = fake()->randomElement([30, 60]);
        $end_minute = $start_minute + $duration_minutes;
        $end_hour = $start_hour + intdiv($end_minute, 60);
        $end_time = sprintf('%02d:%02d:00', $end_hour, $end_minute % 60);

        $created_at = fake()->dateTimeBetween($patient->created_at, 'yesterday');

        return [
            'user_id' => $provider->id,
            'patient_id' => $patient->id,
            'date' => fake()->dateTimeBetween($patient->created_at, '+6 months'),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => fake()->randomElement(AppointmentStatus::cases()),
            'reason' => fake()->randomElement([
                'Annual physical exam',
                'Follow-up consultation',
                'Routine blood work review',
                'Chronic condition management',
                'Vaccination',
                'New patient intake',
                'Post-surgery follow-up',
                'Prescription renewal',
            ]),
            'notes' => fake()->optional(0.6)->sentence(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => AppointmentStatus::Scheduled]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => AppointmentStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(['status' => AppointmentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => AppointmentStatus::Cancelled]);
    }
}
