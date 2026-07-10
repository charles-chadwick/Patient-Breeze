<?php

namespace App\Policies;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class EncounterNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_encounter_notes');
    }

    public function view(User $user, EncounterNote $note): bool
    {
        return $user->can('view_encounter_notes');
    }

    public function create(User $user): bool
    {
        return $user->can('create_encounter_notes');
    }

    public function update(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes') && $note->isEditable();
    }

    public function delete(User $user, EncounterNote $note): bool
    {
        return $user->can('delete_encounter_notes') && $note->isEditable();
    }

    public function sign(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes')
            && $note->status === EncounterNoteStatus::Unsigned
            && $user->id === $note->author_id;
    }

    public function coSign(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes')
            && $note->status === EncounterNoteStatus::Signed
            && $user->id !== $note->signed_by;
    }
}
