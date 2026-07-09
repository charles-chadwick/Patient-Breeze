<?php

namespace App\Http\Controllers;

use App\Actions\CreateNoteAction;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request, CreateNoteAction $createNote): RedirectResponse
    {
        $this->authorize('create', Note::class);

        $createNote->execute($request->validated());

        return redirect()->back()->with('success', __('flash.notes.created'));
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return redirect()->back()->with('success', __('flash.notes.updated'));
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->back()->with('success', __('flash.notes.deleted'));
    }
}
