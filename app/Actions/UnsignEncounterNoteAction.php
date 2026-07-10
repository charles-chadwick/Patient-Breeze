<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class UnsignEncounterNoteAction
{
    public function execute(EncounterNote $note, User $user): void
    {
        $note->forceFill([
            'status' => EncounterNoteStatus::Unsigned,
            'signed_by' => null,
            'signed_at' => null,
            'co_signed_by' => null,
            'co_signed_at' => null,
        ])->save();

        activity()->performedOn($note)->causedBy($user)->event('unsigned')->log('unsigned');
    }
}
