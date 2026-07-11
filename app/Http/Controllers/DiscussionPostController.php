<?php

namespace App\Http\Controllers;

use App\Actions\CreateDiscussionPostAction;
use App\Http\Requests\StoreDiscussionPostRequest;
use App\Http\Requests\UpdateDiscussionPostRequest;
use App\Models\Discussion;
use App\Models\DiscussionPost;
use Illuminate\Http\RedirectResponse;

class DiscussionPostController extends Controller
{
    public function store(StoreDiscussionPostRequest $request, Discussion $discussion, CreateDiscussionPostAction $createPost): RedirectResponse
    {
        $this->authorize('update', $discussion);

        $createPost->execute($discussion, $request->validated()['content'], auth()->id());

        return redirect()->back()->with('success', __('flash.discussion_posts.created'));
    }

    public function update(UpdateDiscussionPostRequest $request, Discussion $discussion, DiscussionPost $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update($request->validated());

        return redirect()->back()->with('success', __('flash.discussion_posts.updated'));
    }

    public function destroy(Discussion $discussion, DiscussionPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->back()->with('success', __('flash.discussion_posts.deleted'));
    }
}
