<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;

class CreateEncounterNoteAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Patient $patient, User $author, array $validated): EncounterNote
    {
        $note = $patient->encounterNotes()->make([
            'type' => $validated['type'],
            'encounter_date' => $validated['encounter_date'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'appointment_id' => $validated['appointment_id'] ?? null,
        ]);

        $note->author_id = $validated['author_id'] ?? $author->id;
        $note->status = EncounterNoteStatus::Unsigned;
        $note->save();

        return $note;
    }
}
