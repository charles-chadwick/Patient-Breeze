<?php

namespace App\Enums;

enum ToggleValue: string
{
    case Enabled = 'Enabled';
    case Disabled = 'Disabled';

    public function label(): string
    {
        return __('enums.toggle_value.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
