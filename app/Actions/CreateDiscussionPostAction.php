<?php

namespace App\Actions;

use App\Enums\DiscussionPostStatus;
use App\Events\DiscussionPostCreated;
use App\Models\Discussion;
use App\Models\DiscussionPost;

class CreateDiscussionPostAction
{
    public function execute(Discussion $discussion, string $content, int $author_id): DiscussionPost
    {
        /** @var DiscussionPost $post */
        $post = $discussion->posts()->create([
            'user_id' => $author_id,
            'status' => DiscussionPostStatus::Published,
            'content' => $content,
        ]);

        DiscussionPostCreated::dispatch($post);

        return $post;
    }
}
