<?php

namespace App\Enums;

enum ContactType: string
{
    case Personal = 'Personal';
    case Work = 'Work';
    case Emergency = 'Emergency';
    case Guardian = 'Guardian';
    case Spouse = 'Spouse';
    case Other = 'Other';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
