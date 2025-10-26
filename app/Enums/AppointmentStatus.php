<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Confirmed = 'Confirmed';
    case Cancelled = 'Cancelled';
    case Completed = 'Completed';
    case Pending = 'Pending';
    case Rescheduled = 'Rescheduled';
    case NoShow = 'No Show';
}
