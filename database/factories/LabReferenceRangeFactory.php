<?php

namespace Database\Factories;

use App\Enums\GenderAtBirth;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LabReferenceRange>
 */
class LabReferenceRangeFactory extends Factory
{
    protected $model = LabReferenceRange::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $low = $this->faker->numberBetween(1, 50);
        $high = $low + $this->faker->numberBetween(5, 50);

        return [
            'lab_order_id' => LabOrder::factory(),
            'gender_at_birth' => null,
            'min_age' => null,
            'max_age' => null,
            'low_value' => (string) $low,
            'high_value' => (string) $high,
            'unit' => $this->faker->randomElement(['g/dL', 'mg/dL', 'mmol/L', '%', 'mIU/L']),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function forGender(GenderAtBirth $gender): static
    {
        return $this->state(fn (): array => ['gender_at_birth' => $gender->value]);
    }

    public function forAges(?int $min, ?int $max): static
    {
        return $this->state(fn (): array => ['min_age' => $min, 'max_age' => $max]);
    }
}
