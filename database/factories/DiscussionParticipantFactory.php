<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\DiscussionParticipant;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscussionParticipant>
 */
class DiscussionParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'discussion_id' => Discussion::factory(),
            'participantable_id' => User::factory(),
            'participantable_type' => User::class,
            'seen_at' => null,
        ];
    }

    public function forPatient(?Patient $patient = null): static
    {
        return $this->state(function () use ($patient) {
            $patient ??= Patient::factory()->create();

            return [
                'participantable_id' => $patient->id,
                'participantable_type' => Patient::class,
            ];
        });
    }

    public function initiator(): static
    {
        return $this->state(['is_initiator' => true]);
    }

    public function seen(): static
    {
        return $this->state(['seen_at' => now()]);
    }
}
