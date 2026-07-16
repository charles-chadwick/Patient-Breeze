<?php

namespace Database\Factories;

use App\Enums\BodyPosition;
use App\Enums\OxygenDelivery;
use App\Enums\TemperatureSite;
use App\Models\Patient;
use App\Models\PatientVitals;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PatientVitals>
 */
class PatientVitalsFactory extends Factory
{
    protected $model = PatientVitals::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $measured_at = Carbon::parse($this->faker->dateTimeBetween('-2 years', 'now'));

        return [
            'patient_id' => Patient::factory(),
            'appointment_id' => null,
            'recorded_by' => User::factory(),
            'measured_at' => $measured_at,
            'systolic' => $this->faker->numberBetween(100, 145),
            'diastolic' => $this->faker->numberBetween(60, 95),
            'position' => $this->faker->randomElement(BodyPosition::cases()),
            'heart_rate' => $this->faker->numberBetween(55, 105),
            'respiratory_rate' => $this->faker->numberBetween(12, 22),
            'temperature' => $this->faker->randomFloat(1, 36.2, 38.2),
            'temperature_site' => $this->faker->randomElement(TemperatureSite::cases()),
            'oxygen_saturation' => $this->faker->numberBetween(93, 100),
            'oxygen_delivery' => OxygenDelivery::RoomAir,
            'weight' => $this->faker->randomFloat(2, 50, 110),
            'height' => $this->faker->randomFloat(2, 150, 195),
            'pain_score' => $this->faker->numberBetween(0, 6),
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
