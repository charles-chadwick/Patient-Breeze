<?php

namespace App\Enums;

enum AllergyReaction: string
{
    case Anaphylaxis = 'Anaphylaxis';
    case Hives = 'Hives';
    case Rash = 'Rash';
    case Itching = 'Itching';
    case Swelling = 'Swelling';
    case ShortnessOfBreath = 'Shortness Of Breath';
    case Wheezing = 'Wheezing';
    case Nausea = 'Nausea';
    case Vomiting = 'Vomiting';
    case Diarrhea = 'Diarrhea';
    case Dizziness = 'Dizziness';
    case Other = 'Other';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.allergy_reaction.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
