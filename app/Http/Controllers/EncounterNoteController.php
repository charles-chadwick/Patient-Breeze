<?php

namespace App\Http\Controllers;

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\CreateEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Http\Requests\StoreEncounterNoteRequest;
use App\Http\Requests\UpdateEncounterNoteRequest;
use App\Models\EncounterNote;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EncounterNoteController extends Controller
{
    public function store(StoreEncounterNoteRequest $request, Patient $patient, CreateEncounterNoteAction $createNote): RedirectResponse
    {
        $this->authorize('create', EncounterNote::class);

        $createNote->execute($patient, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('flash.encounter_notes.created'));
    }

    public function update(UpdateEncounterNoteRequest $request, Patient $patient, EncounterNote $encounterNote): RedirectResponse
    {
        $this->authorize('update', $encounterNote);

        $encounterNote->update($request->validated());

        return redirect()->back()->with('success', __('flash.encounter_notes.updated'));
    }

    public function destroy(Patient $patient, EncounterNote $encounterNote): RedirectResponse
    {
        $this->authorize('delete', $encounterNote);

        $encounterNote->delete();

        return redirect()->back()->with('success', __('flash.encounter_notes.deleted'));
    }

    public function sign(Request $request, Patient $patient, EncounterNote $encounterNote, SignEncounterNoteAction $sign): RedirectResponse
    {
        $this->authorize('sign', $encounterNote);

        $sign->execute($encounterNote, $request->user());

        return redirect()->back()->with('success', __('flash.encounter_notes.signed'));
    }

    public function coSign(Request $request, Patient $patient, EncounterNote $encounterNote, CoSignEncounterNoteAction $coSign): RedirectResponse
    {
        $this->authorize('coSign', $encounterNote);

        $coSign->execute($encounterNote, $request->user());

        return redirect()->back()->with('success', __('flash.encounter_notes.co_signed'));
    }
}
