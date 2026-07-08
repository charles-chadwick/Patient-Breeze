<?php

namespace App\Enums;

enum AppointmentRole: string
{
    case Primary = 'Primary';
    case Assistant = 'Assistant';

    public function label(): string
    {
        return __('enums.appointment_role.'.$this->value);
    }
}
