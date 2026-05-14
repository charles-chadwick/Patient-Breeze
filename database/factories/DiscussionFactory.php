<?php

namespace Database\Factories;

use App\Enums\DiscussionType;
use App\Models\Discussion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DiscussionFactory extends Factory
{
    protected $model = Discussion::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(DiscussionType::cases()),

            'title' => $this->faker->word(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
