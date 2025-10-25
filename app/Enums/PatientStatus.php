<?php

namespace App\Enums;

enum PatientStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Deceased = 'Deceased';
    case Prospective = 'Prospective';
}
