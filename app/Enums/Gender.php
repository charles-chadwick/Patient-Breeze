<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum Gender: string
{
    use EnumToArray;

    case Male    = 'Male';
    case Female  = 'Female';
    case Unknown = 'Unknown';
}
