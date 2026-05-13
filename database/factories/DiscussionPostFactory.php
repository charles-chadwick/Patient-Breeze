<?php

namespace Database\Factories;

use App\Enums\DiscussionPostStatus;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscussionPostFactory extends Factory
{
    protected $model = DiscussionPost::class;

    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(DiscussionPostStatus::cases()),
            'content' => fake()->sentence(),
            'discussion_id' => Discussion::factory(),
        ];
    }
}
