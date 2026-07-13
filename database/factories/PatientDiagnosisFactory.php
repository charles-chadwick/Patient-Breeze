<?php

namespace Database\Factories;

use App\Enums\DiagnosisStatus;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PatientDiagnosisFactory extends Factory
{
    protected $model = PatientDiagnosis::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $letter = $this->faker->randomLetter();
        $icd10_code = strtoupper($letter).$this->faker->numberBetween(10, 99).'.'.$this->faker->numberBetween(0, 9);

        return [
            'patient_id' => Patient::factory(),
            'diagnosis' => ucfirst($this->faker->words(3, true)),
            'icd10_code' => $icd10_code,
            'diagnosed_on' => Carbon::parse($this->faker->dateTimeBetween('-3 years', 'now'))->toDateString(),
            'status' => $this->faker->randomElement(DiagnosisStatus::cases()),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
