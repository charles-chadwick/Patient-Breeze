<?php

namespace App\Enums;

enum VaccineSite: string
{
    case LeftDeltoid = 'Left Deltoid';
    case RightDeltoid = 'Right Deltoid';
    case LeftThigh = 'Left Thigh';
    case RightThigh = 'Right Thigh';
    case LeftGluteus = 'Left Gluteus';
    case RightGluteus = 'Right Gluteus';
    case Oral = 'Oral';
    case Nasal = 'Nasal';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.vaccine_site.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
