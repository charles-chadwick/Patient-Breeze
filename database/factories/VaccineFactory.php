<?php

namespace Database\Factories;

use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Vaccine>
 */
class VaccineFactory extends Factory
{
    protected $model = Vaccine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'cvx_code' => (string) $this->faker->unique()->numberBetween(100, 999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
