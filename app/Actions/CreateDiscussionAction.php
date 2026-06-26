<?php

namespace App\Actions;

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Models\Discussion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDiscussionAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated, int $initiator_id): Discussion
    {
        return DB::transaction(function () use ($validated, $initiator_id) {
            /** @var Model $discussionable */
            $discussionable = ($validated['discussionable_type'])::findOrFail($validated['discussionable_id']);

            $type = DiscussionType::from($validated['type']);

            $discussion = $discussionable->discussions()->create([
                'type' => $type,
                'title' => $validated['title'],
                'status' => 'Open',
            ]);

            $discussion->participants()->create([
                'participantable_type' => User::class,
                'participantable_id' => $initiator_id,
                'is_initiator' => true,
            ]);

            foreach ($validated['participant_ids'] ?? [] as $participant_id) {
                if ($participant_id !== $initiator_id) {
                    $discussion->participants()->create([
                        'participantable_type' => User::class,
                        'participantable_id' => $participant_id,
                        'is_initiator' => false,
                    ]);
                }
            }

            if ($type === DiscussionType::PortalMessage) {
                $discussion->participants()->create([
                    'participantable_type' => $validated['discussionable_type'],
                    'participantable_id' => $validated['discussionable_id'],
                    'is_initiator' => false,
                ]);
            }

            $discussion->posts()->create([
                'user_id' => $initiator_id,
                'status' => DiscussionPostStatus::Published,
                'content' => $validated['initial_reply'],
            ]);

            return $discussion;
        });
    }
}
