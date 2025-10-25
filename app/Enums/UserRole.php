<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'Super Admin';
    case Doctor = 'Doctor';
    case Nurse = 'Nurse';
    case Admin = 'Admin';
    case Staff = 'Staff';
}
