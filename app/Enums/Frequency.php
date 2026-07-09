<?php

namespace App\Enums;

enum Frequency: string
{
    case OnceDaily = 'Once Daily';
    case TwiceDaily = 'Twice Daily';
    case ThreeTimesDaily = 'Three Times Daily';
    case FourTimesDaily = 'Four Times Daily';
    case EveryMorning = 'Every Morning';
    case EveryNight = 'Every Night';
    case EveryOtherDay = 'Every Other Day';
    case Weekly = 'Weekly';
    case Monthly = 'Monthly';
    case AsNeeded = 'As Needed';

    public function label(): string
    {
        return __('enums.frequency.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
