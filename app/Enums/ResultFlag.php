<?php

namespace App\Enums;

enum ResultFlag: string
{
    case Normal = 'Normal';
    case Low = 'Low';
    case High = 'High';
    case Unknown = 'Unknown';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.result_flag.'.$this->value);
    }

    /**
     * Whether this flag represents an out-of-range result.
     */
    public function isAbnormal(): bool
    {
        return $this === self::Low || $this === self::High;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
