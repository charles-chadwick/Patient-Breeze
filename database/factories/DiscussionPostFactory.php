<?php

namespace Database\Factories;

use App\Enums\DiscussionPostStatus;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscussionPostFactory extends Factory
{
    protected $model = DiscussionPost::class;

    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(DiscussionPostStatus::cases()),
            'content' => fake()->sentence(),
        ];
    }

    public function fromPatient(?Patient $patient = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'patient_id' => $patient?->id ?? Patient::factory(),
        ]);
    }
}
