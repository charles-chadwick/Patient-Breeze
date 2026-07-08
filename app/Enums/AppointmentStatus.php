<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'Scheduled';
    case Confirmed = 'Confirmed';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
    case Rescheduled = 'Rescheduled';
    case NoShow = 'NoShow';

    public function label(): string
    {
        return __('enums.appointment_status.'.$this->value);
    }
}
