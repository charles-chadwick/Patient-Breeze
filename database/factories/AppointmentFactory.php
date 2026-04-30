<?php

namespace Database\Factories;

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $patient = Patient::inRandomOrder()->first()
            ?? Patient::factory()->create();

        $start_hour = fake()->numberBetween(8, 16);
        $start_minute = fake()->randomElement([0, 30]);
        $start_time = sprintf('%02d:%02d:00', $start_hour, $start_minute);

        $duration_minutes = fake()->randomElement([30, 60]);
        $end_minute = $start_minute + $duration_minutes;
        $end_hour = $start_hour + intdiv($end_minute, 60);
        $end_time = sprintf('%02d:%02d:00', $end_hour, $end_minute % 60);

        $created_at_floor = Carbon::parse($patient->created_at)->min(now()->subDays(2));
        $created_at = fake()->dateTimeBetween($created_at_floor, 'yesterday');

        return [
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

    public function configure(): static
    {
        return $this->afterCreating(function (Appointment $appointment): void {
            if ($appointment->users()->exists()) {
                return;
            }

            $appointment->attachProvider($this->resolveStaff(), AppointmentRole::Primary);
        });
    }

    public function withProvider(User $user, AppointmentRole $role = AppointmentRole::Primary): static
    {
        return $this->afterCreating(function (Appointment $appointment) use ($user, $role): void {
            $appointment->users()->detach();
            $appointment->attachProvider($user, $role);
        });
    }

    public function withProviders(int $count): static
    {
        return $this->afterCreating(function (Appointment $appointment) use ($count): void {
            $appointment->users()->detach();

            $roles = [AppointmentRole::Primary, AppointmentRole::Assistant];
            $excluded_ids = [];

            for ($i = 0; $i < $count; $i++) {
                $staff = $this->resolveStaff($excluded_ids);
                $excluded_ids[] = $staff->id;

                $appointment->attachProvider($staff, $roles[$i] ?? AppointmentRole::Assistant);
            }
        });
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

    /**
     * @param  array<int, int>  $excluded_ids
     */
    private function resolveStaff(array $excluded_ids = []): User
    {
        return User::staff()
            ->whereNotIn('id', $excluded_ids)
            ->inRandomOrder()
            ->first()
            ?? throw new \RuntimeException('No staff users found. Run UserSeeder first.');
    }
}
