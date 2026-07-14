<?php

namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientLabResult;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PatientLabResult>
 */
class PatientLabResultFactory extends Factory
{
    protected $model = PatientLabResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'lab_order_id' => LabOrder::factory(),
            'name' => ucfirst($this->faker->words(2, true)),
            'performing_lab' => $this->faker->randomElement(['Quest Diagnostics', 'Labcorp', 'Hospital Core Laboratory']),
            'cpt_code' => (string) $this->faker->numberBetween(80000, 89999),
            'value' => (string) $this->faker->randomFloat(2, 1, 200),
            'unit' => $this->faker->randomElement(['g/dL', 'mg/dL', 'mmol/L', '%']),
            'reference_low' => '10',
            'reference_high' => '20',
            'reference_gender' => null,
            'reference_age' => $this->faker->numberBetween(18, 90),
            'collected_at' => Carbon::now()->toDateString(),
            'notes' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
