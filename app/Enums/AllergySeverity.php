<?php

namespace App\Enums;

enum AllergySeverity: string
{
    case Mild = 'Mild';
    case Moderate = 'Moderate';
    case Severe = 'Severe';
    case LifeThreatening = 'Life-Threatening';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.allergy_severity.'.$this->value);
    }

    /**
     * Whether this severity warrants the chart's high-visibility allergy banner
     * treatment rather than the muted one.
     */
    public function isCritical(): bool
    {
        return $this === self::Severe || $this === self::LifeThreatening;
    }

    /**
     * Sort weight, most dangerous first, so the banner leads with the allergy
     * that matters most.
     */
    public function rank(): int
    {
        return match ($this) {
            self::LifeThreatening => 0,
            self::Severe => 1,
            self::Moderate => 2,
            self::Mild => 3,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
