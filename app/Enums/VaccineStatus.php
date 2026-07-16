<?php

namespace App\Enums;

enum VaccineStatus: string
{
    case Completed = 'Completed';
    case NotAdministered = 'Not Administered';
    case Refused = 'Refused';
    case EnteredInError = 'Entered In Error';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.vaccine_status.'.$this->value);
    }

    /**
     * Whether this status means the dose actually went into the patient, and so
     * counts towards their immunization history.
     */
    public function isAdministered(): bool
    {
        return $this === self::Completed;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
