<?php

namespace App\Enums;

enum NoteType: string
{
    case General = 'General';
    case Clinical = 'Clinical';
    case Administrative = 'Administrative';
    case CarePlan = 'CarePlan';

    public function label(): string
    {
        return __('enums.note_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
