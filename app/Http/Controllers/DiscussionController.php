<?php

namespace App\Http\Controllers;

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionType;
use App\Http\Requests\StoreDiscussionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DiscussionController extends Controller
{
    public function store(StoreDiscussionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $discussionable = ($validated['discussionable_type'])::findOrFail($validated['discussionable_id']);

        $type = DiscussionType::from($validated['type']);

        $user_id = auth()->id();

        $discussion = $discussionable->discussions()->create([
            'type' => $type,
            'title' => $validated['title'],
            'status' => 'Open',
        ]);

        $discussion->participants()->create([
            'participantable_type' => User::class,
            'participantable_id' => $user_id,
            'is_initiator' => true,
        ]);

        foreach ($validated['participant_ids'] ?? [] as $participant_id) {
            if ($participant_id !== $user_id) {
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
            'user_id' => $user_id,
            'status' => DiscussionPostStatus::Published,
            'content' => $validated['initial_reply'],
        ]);

        return redirect()->back();
    }
}
