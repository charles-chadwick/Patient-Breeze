<?php

namespace App\Broadcasting;

use App\Models\Discussion;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscussionChannel
{
    /**
     * Authenticate the user's access to the discussion channel.
     *
     * Both staff (User) and portal patients (Patient) participate in
     * discussions polymorphically, so authorization is granted only when the
     * authenticated entity is a participant of the requested discussion.
     */
    public function join(Authenticatable $user, int $discussionId): bool
    {
        if (! $user instanceof Model) {
            return false;
        }

        return Discussion::whereKey($discussionId)
            ->whereHas('participants', function (Builder $query) use ($user): void {
                $query->where('participantable_type', $user->getMorphClass())
                    ->where('participantable_id', $user->getKey());
            })
            ->exists();
    }
}
