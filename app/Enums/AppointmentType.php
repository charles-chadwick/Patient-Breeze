<?php

namespace App\Enums;

/**
 * This enum is temporary until an actual appointment type is defined.
 */
enum AppointmentType: string
{
    case InOffice = 'In Office';
    case Online = 'Online';
    case Phone = 'Phone';
}
