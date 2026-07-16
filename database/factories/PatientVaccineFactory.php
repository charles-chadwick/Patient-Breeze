<?php

namespace Database\Factories;

use App\Enums\VaccineRoute;
use App\Enums\VaccineSite;
use App\Enums\VaccineStatus;
use App\Models\Patient;
use App\Models\PatientVaccine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PatientVaccine>
 */
class PatientVaccineFactory extends Factory
{
    protected $model = PatientVaccine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $administered_on = Carbon::parse($this->faker->dateTimeBetween('-5 years', 'now'));

        return [
            'patient_id' => Patient::factory(),
            'vaccine' => ucfirst($this->faker->words(2, true)),
            'cvx_code' => (string) $this->faker->numberBetween(100, 999),
            'administered_on' => $administered_on->toDateString(),
            'dose_number' => $this->faker->numberBetween(1, 3),
            'status' => VaccineStatus::Completed,
            'route' => $this->faker->randomElement(VaccineRoute::cases()),
            'site' => $this->faker->randomElement(VaccineSite::cases()),
            'dose_amount' => $this->faker->randomElement(['0.25 mL', '0.5 mL', '1 mL']),
            'manufacturer' => $this->faker->randomElement(['Pfizer', 'Moderna', 'Merck', 'Sanofi', 'GSK']),
            'lot_number' => strtoupper($this->faker->bothify('??####')),
            'expires_on' => $administered_on->copy()->addMonths($this->faker->numberBetween(1, 24))->toDateString(),
            'administered_by' => User::factory(),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function status(VaccineStatus $status): self
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
