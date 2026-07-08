<?php

namespace App\Http\Controllers;

use App\Actions\CreateDiscussionAction;
use App\Http\Requests\StoreDiscussionRequest;
use Illuminate\Http\RedirectResponse;

class DiscussionController extends Controller
{
    public function store(StoreDiscussionRequest $request, CreateDiscussionAction $createDiscussion): RedirectResponse
    {
        $createDiscussion->execute($request->validated(), auth()->id());

        return redirect()->back()->with('success', __('flash.discussions.created'));
    }
}
