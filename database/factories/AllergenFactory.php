<?php

namespace Database\Factories;

use App\Enums\AllergenCategory;
use App\Models\Allergen;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Allergen>
 */
class AllergenFactory extends Factory
{
    protected $model = Allergen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'category' => $this->faker->randomElement(AllergenCategory::cases()),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function category(AllergenCategory $category): self
    {
        return $this->state(fn (): array => ['category' => $category]);
    }
}
