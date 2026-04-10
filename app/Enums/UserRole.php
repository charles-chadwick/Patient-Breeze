<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Doctor = 'Doctor';
    case Nurse = 'Nurse';
    case MedicalAssistant = 'Medical Assistant';
    case Staff = 'Staff';
    case Patient = 'Patient';
}
