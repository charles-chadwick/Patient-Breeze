<?php

namespace App\Enums;

enum AppointmentRequestStatus: string
{
    case Pending = 'Pending';
    case Approved = 'Approved';
    case Declined = 'Declined';

    public function label(): string
    {
        return __('enums.appointment_request_status.'.$this->value);
    }
}
