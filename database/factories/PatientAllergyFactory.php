<?php

namespace Database\Factories;

use App\Enums\AllergenCategory;
use App\Enums\AllergyReaction;
use App\Enums\AllergySeverity;
use App\Enums\AllergyStatus;
use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PatientAllergy>
 */
class PatientAllergyFactory extends Factory
{
    protected $model = PatientAllergy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reactions = $this->faker->randomElements(AllergyReaction::values(), $this->faker->numberBetween(1, 3));

        return [
            'patient_id' => Patient::factory(),
            'allergen' => ucfirst($this->faker->words(2, true)),
            'category' => $this->faker->randomElement(AllergenCategory::cases()),
            'reactions' => $reactions,
            'severity' => $this->faker->randomElement(AllergySeverity::cases()),
            'status' => AllergyStatus::Active,
            'onset_on' => Carbon::parse($this->faker->dateTimeBetween('-10 years', 'now'))->toDateString(),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function severity(AllergySeverity $severity): self
    {
        return $this->state(fn (): array => ['severity' => $severity]);
    }

    public function status(AllergyStatus $status): self
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
