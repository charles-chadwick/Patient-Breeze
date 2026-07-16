<?php

namespace App\Enums;

enum VaccineRoute: string
{
    case Intramuscular = 'Intramuscular';
    case Subcutaneous = 'Subcutaneous';
    case Intradermal = 'Intradermal';
    case Oral = 'Oral';
    case Intranasal = 'Intranasal';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.vaccine_route.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
