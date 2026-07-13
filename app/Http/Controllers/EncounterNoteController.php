<?php

namespace App\Http\Controllers;

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\CreateEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Actions\UnsignEncounterNoteAction;
use App\Enums\EncounterNoteStatus;
use App\Http\Requests\StoreEncounterNoteRequest;
use App\Http\Requests\UpdateEncounterNoteRequest;
use App\Models\EncounterNote;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EncounterNoteController extends Controller
{
    /**
     * A cross-patient worklist of notes awaiting co-signature.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', EncounterNote::class);

        $notes = EncounterNote::query()
            ->where('status', EncounterNoteStatus::Signed)
            ->with([
                'patient:id,first_name,last_name',
                'signer:id,first_name,last_name',
            ])
            ->orderBy('signed_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('EncounterNotes/Index', [
            'notes' => $notes,
        ]);
    }

    public function store(StoreEncounterNoteRequest $request, Patient $patient, CreateEncounterNoteAction $createNote): RedirectResponse
    {
        $this->authorize('create', EncounterNote::class);

        $createNote->execute($patient, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('flash.encounter_notes.created'));
    }

    public function update(UpdateEncounterNoteRequest $request, Patient $patient, EncounterNote $encounterNote, SignEncounterNoteAction $sign): RedirectResponse
    {
        $this->authorize('update', $encounterNote);

        $validated = $request->validated();

        $encounterNote->fill($validated);
        $encounterNote->author_id = $validated['author_id'] ?? $encounterNote->author_id;
        $encounterNote->save();

        if ($request->boolean('sign')) {
            $this->authorize('sign', $encounterNote);

            $sign->execute($encounterNote, $request->user());

            return redirect()->back()->with('success', __('flash.encounter_notes.updated_and_signed'));
        }

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

    public function unsign(Request $request, Patient $patient, EncounterNote $encounterNote, UnsignEncounterNoteAction $unsign): RedirectResponse
    {
        $this->authorize('unsign', $encounterNote);

        $unsign->execute($encounterNote, $request->user());

        return redirect()->back()->with('success', __('flash.encounter_notes.unsigned'));
    }
}
