<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DiscussionPost;
use App\Models\User;

class DiscussionPostPolicy
{
    /**
     * Staff may edit only their own posts. Patient-authored posts (user_id null)
     * are never editable by staff.
     */
    public function update(User $user, DiscussionPost $post): bool
    {
        return $user->can('update_discussions') && $this->isAuthor($user, $post);
    }

    /**
     * Staff may retract only their own posts.
     */
    public function delete(User $user, DiscussionPost $post): bool
    {
        return $user->can('delete_discussions') && $this->isAuthor($user, $post);
    }

    public function forceDelete(User $user, DiscussionPost $post): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }

    private function isAuthor(User $user, DiscussionPost $post): bool
    {
        return $post->user_id !== null && $post->user_id === $user->id;
    }
}
