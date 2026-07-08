<?php

namespace App\Http\Controllers;

use App\Actions\CreateDiscussionPostAction;
use App\Http\Requests\StoreDiscussionPostRequest;
use App\Models\Discussion;
use Illuminate\Http\RedirectResponse;

class DiscussionPostController extends Controller
{
    public function store(StoreDiscussionPostRequest $request, Discussion $discussion, CreateDiscussionPostAction $createPost): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $createPost->execute($discussion, $request->validated()['content'], auth()->id());

        return redirect()->back()->with('success', __('flash.discussion_posts.created'));
    }
}
