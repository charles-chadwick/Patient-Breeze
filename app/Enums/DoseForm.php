<?php

namespace App\Enums;

enum DoseForm: string
{
    case Tablet = 'Tablet';
    case Capsule = 'Capsule';
    case Solution = 'Solution';
    case Suspension = 'Suspension';
    case Syrup = 'Syrup';
    case Elixir = 'Elixir';
    case Injection = 'Injection';
    case Cream = 'Cream';
    case Ointment = 'Ointment';
    case Gel = 'Gel';
    case Lotion = 'Lotion';
    case Suppository = 'Suppository';
    case Patch = 'Patch';
    case Inhaler = 'Inhaler';
    case Drops = 'Drops';
    case Spray = 'Spray';
    case Powder = 'Powder';
    case Lozenge = 'Lozenge';
    case Foam = 'Foam';
    case Granules = 'Granules';

    public function label(): string
    {
        return __('enums.dose_form.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
