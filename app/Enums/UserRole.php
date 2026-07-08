<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Doctor = 'Doctor';
    case Nurse = 'Nurse';
    case MedicalAssistant = 'Medical Assistant';
    case Staff = 'Staff';

    /**
     * Human-facing, translatable label. The backing value stays as stored data.
     */
    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }
}
