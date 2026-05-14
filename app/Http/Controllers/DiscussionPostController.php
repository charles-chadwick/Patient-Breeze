<?php

namespace App\Http\Controllers;

use App\Enums\DiscussionPostStatus;
use App\Http\Requests\StoreDiscussionPostRequest;
use App\Models\Discussion;
use Illuminate\Http\RedirectResponse;

class DiscussionPostController extends Controller
{
    public function store(StoreDiscussionPostRequest $request, Discussion $discussion): RedirectResponse
    {
        $discussion->posts()->create([
            'user_id' => auth()->id(),
            'status' => DiscussionPostStatus::Published,
            'content' => $request->validated()['content'],
        ]);

        return redirect()->back();
    }
}
