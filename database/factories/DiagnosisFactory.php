<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $letter = $this->faker->randomLetter();
        $icd10_code = strtoupper($letter).$this->faker->numberBetween(10, 99).'.'.$this->faker->numberBetween(0, 9);

        return [
            'diagnosis' => ucfirst($this->faker->words(3, true)),
            'icd10_code' => $icd10_code,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
