<?php

namespace App\Enums;

enum BodyPosition: string
{
    case Sitting = 'Sitting';
    case Standing = 'Standing';
    case Supine = 'Supine';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.body_position.'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
