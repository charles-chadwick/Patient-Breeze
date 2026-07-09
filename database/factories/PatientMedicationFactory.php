<?php

namespace Database\Factories;

use App\Enums\DoseForm;
use App\Enums\Frequency;
use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PatientMedicationFactory extends Factory
{
    protected $model = PatientMedication::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'type' => $this->faker->word(),

            'name' => $this->faker->name(),
            'dosage' => $this->faker->word(),
            'dose_form' => $this->faker->randomElement(DoseForm::cases()),
            'frequency' => $this->faker->randomElement(Frequency::cases()),
            'amount' => $this->faker->numberBetween(1, 3).' tablets',
            'ndc' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
