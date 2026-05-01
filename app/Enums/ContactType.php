<?php

namespace App\Enums;

enum ContactType: string
{
    case Personal = 'Personal';
    case Work = 'Work';
    case Emergency = 'Emergency';
    case Guardian = 'Guardian';
    case Spouse = 'Spouse';
    case Other = 'Other';
}
