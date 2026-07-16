<?php

namespace App\Enums;

enum InsurancePriority: string
{
    case Primary = 'Primary';
    case Secondary = 'Secondary';
    case Tertiary = 'Tertiary';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.insurance_priority.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
