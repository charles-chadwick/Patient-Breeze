<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum UserRole: string
{
    use EnumToArray;

    case SuperAdmin = 'Super Admin';
    case Doctor     = 'Doctor';
    case Nurse      = 'Nurse';
    case Admin      = 'Admin';
    case Staff      = 'Staff';
}
