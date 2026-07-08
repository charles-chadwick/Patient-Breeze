<?php

namespace App\Enums;

enum GenderAtBirth: string
{
    case Male = 'Male';
    case Female = 'Female';
    case Unknown = 'Unknown';

    public function label(): string
    {
        return __('enums.gender_at_birth.'.$this->value);
    }
}
