<?php

namespace Database\Factories;

use App\Enums\DoseForm;
use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),

            'name' => $this->faker->name(),
            'dosage' => $this->faker->word(),
            'dose_form' => $this->faker->randomElement(DoseForm::cases()),
            'ndc' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
