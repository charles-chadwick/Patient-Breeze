<?php

namespace App\Enums;

enum EncounterNoteType: string
{
    case Progress = 'Progress';
    case InitialVisit = 'InitialVisit';
    case FollowUp = 'FollowUp';
    case Consultation = 'Consultation';
    case Procedure = 'Procedure';
    case DischargeSummary = 'DischargeSummary';
    case Telephone = 'Telephone';

    public function label(): string
    {
        return __('enums.encounter_note_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
