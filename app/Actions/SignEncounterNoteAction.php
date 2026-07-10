<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class SignEncounterNoteAction
{
    public function execute(EncounterNote $note, User $user): void
    {
        $note->forceFill([
            'status' => EncounterNoteStatus::Signed,
            'signed_by' => $user->id,
            'signed_at' => now(),
        ])->save();

        activity()->performedOn($note)->causedBy($user)->event('signed')->log('signed');
    }
}
