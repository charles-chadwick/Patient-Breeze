<?php

namespace App\Http\Controllers;

use App\Actions\CreateDiscussionAction;
use App\Http\Requests\StoreDiscussionRequest;
use App\Models\Discussion;
use Illuminate\Http\RedirectResponse;

class DiscussionController extends Controller
{
    public function store(StoreDiscussionRequest $request, CreateDiscussionAction $createDiscussion): RedirectResponse
    {
        $this->authorize('create', Discussion::class);

        $createDiscussion->execute($request->validated(), auth()->id());

        return redirect()->back();
    }
}
