<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PatientStatus: string
{
    use EnumToArray;

    case Active      = 'Active';
    case Inactive    = 'Inactive';
    case Deceased    = 'Deceased';
    case Prospective = 'Prospective';
}
