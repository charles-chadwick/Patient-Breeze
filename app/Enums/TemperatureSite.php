<?php

namespace App\Enums;

enum TemperatureSite: string
{
    case Oral = 'Oral';
    case Axillary = 'Axillary';
    case Temporal = 'Temporal';
    case Rectal = 'Rectal';
    case Tympanic = 'Tympanic';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.temperature_site.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
