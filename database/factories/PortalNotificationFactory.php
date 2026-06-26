<?php

namespace Database\Factories;

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Models\DiscussionPost;
use App\Models\Patient;
use App\Models\PortalNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortalNotification>
 */
class PortalNotificationFactory extends Factory
{
    public function definition(): array
    {
        $patient = Patient::factory()->create();
        $post = $this->createPost($patient);

        return [
            'type' => 'portal.message.received',
            'notifiable_type' => DiscussionPost::class,
            'notifiable_id' => $post->id,
            'patient_id' => $patient->id,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->sentence(),
            'url' => null,
            'read_at' => null,
        ];
    }

    private function createPost(Patient $patient): DiscussionPost
    {
        $discussion = $patient->discussions()->create([
            'type' => DiscussionType::PortalMessage,
            'title' => 'Portal Message',
            'status' => 'Open',
        ]);

        return $discussion->posts()->create([
            'user_id' => null,
            'patient_id' => $patient->id,
            'status' => DiscussionPostStatus::Published,
            'content' => $this->faker->sentence(),
        ]);
    }
}
