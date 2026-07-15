<?php

namespace App\Enums;

enum AllergenCategory: string
{
    case Drug = 'Drug';
    case Food = 'Food';
    case Environmental = 'Environmental';
    case Other = 'Other';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.allergen_category.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
