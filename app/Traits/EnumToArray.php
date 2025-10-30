<?php

namespace App\Traits;

trait EnumToArray
{
    public static function toArray() : array
    {
        return collect(self::cases())
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'name'  => $role->name,
                ];
            })
            ->toArray();
    }
}