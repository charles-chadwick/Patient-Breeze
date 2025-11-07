<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum AppointmentStatus: string
{
    use EnumToArray;

    case Confirmed = 'Confirmed';
    case Cancelled = 'Cancelled';
    case Completed = 'Completed';
    case Pending = 'Pending';
    case Rescheduled = 'Rescheduled';
    case NoShow = 'No Show';
}
