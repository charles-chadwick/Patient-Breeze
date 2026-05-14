<?php

namespace App\Enums;

enum DiscussionType: string
{
    case General = 'General';
    case Internal = 'Internal';
    case PortalMessage = 'Portal Message';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
