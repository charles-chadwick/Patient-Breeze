<?php

namespace App\Enums;

enum GenderIdentity: string
{
    case Male = 'Male';
    case Female = 'Female';
    case NonBinary = 'Non-binary';
    case PreferNotToSay = 'Prefer not to say';

    public function label(): string
    {
        return __('enums.gender_identity.'.$this->value);
    }
}
