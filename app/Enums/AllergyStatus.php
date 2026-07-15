<?php

namespace App\Enums;

enum AllergyStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Resolved = 'Resolved';
    case EnteredInError = 'Entered In Error';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.allergy_status.'.$this->value);
    }

    /**
     * Whether an allergy in this status should still be surfaced on the chart
     * banner as a live risk.
     */
    public function isCurrent(): bool
    {
        return $this === self::Active;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
