<?php

namespace App\Enums;

enum DiscussionType: string
{
    case General = 'General';
    case Internal = 'Internal';
    case PortalMessage = 'Portal Message';

    public function label(): string
    {
        return __('enums.discussion_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
