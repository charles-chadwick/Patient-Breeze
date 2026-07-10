<?php

namespace App\Enums;

enum EncounterNoteStatus: string
{
    case Unsigned = 'Unsigned';
    case Signed = 'Signed';
    case CoSigned = 'CoSigned';

    public function label(): string
    {
        return __('enums.encounter_note_status.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
