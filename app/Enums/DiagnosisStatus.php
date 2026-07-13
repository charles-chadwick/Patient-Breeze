<?php

namespace App\Enums;

enum DiagnosisStatus: string
{
    case Active = 'Active';
    case Chronic = 'Chronic';
    case InRemission = 'In Remission';
    case Resolved = 'Resolved';
    case RuledOut = 'Ruled Out';

    public function label(): string
    {
        return __('enums.diagnosis_status.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
