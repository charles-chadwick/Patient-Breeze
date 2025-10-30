<?php

namespace App\Enums;

use App\Traits\EnumToArray;

/**
 * This enum is temporary until an actual appointment type is defined.
 */
enum AppointmentType: string
{
    use EnumToArray;

    case InOffice = 'In Office';
    case Online   = 'Online';
    case Phone    = 'Phone';
}
