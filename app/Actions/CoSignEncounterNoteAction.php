<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class CoSignEncounterNoteAction
{
    public function execute(EncounterNote $note, User $user): void
    {
        $note->forceFill([
            'status' => EncounterNoteStatus::CoSigned,
            'co_signed_by' => $user->id,
            'co_signed_at' => now(),
        ])->save();

        activity()->performedOn($note)->causedBy($user)->event('co_signed')->log('co_signed');
    }
}
