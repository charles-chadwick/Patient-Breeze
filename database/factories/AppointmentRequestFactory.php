<?php

namespace Database\Factories;

use App\Enums\AppointmentRequestStatus;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentRequest>
 */
class AppointmentRequestFactory extends Factory
{
    protected $model = AppointmentRequest::class;

    public function definition(): array
    {
        $start_hour = fake()->numberBetween(8, 16);
        $start_minute = fake()->randomElement([0, 30]);
        $start_time = sprintf('%02d:%02d:00', $start_hour, $start_minute);
        $end_time = sprintf('%02d:%02d:00', $start_hour + 1, $start_minute);

        return [
            'patient_id' => Patient::factory(),
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'reason' => fake()->randomElement([
                'Annual physical exam',
                'Follow-up consultation',
                'Prescription renewal',
                'New symptom evaluation',
            ]),
            'notes' => fake()->optional(0.4)->sentence(),
            'status' => AppointmentRequestStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'appointment_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => AppointmentRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(['status' => AppointmentRequestStatus::Approved]);
    }

    public function declined(): static
    {
        return $this->state(['status' => AppointmentRequestStatus::Declined]);
    }

    public function forProvider(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }
}
