<?php
namespace App\Traits;

trait EnumToArray
{
    public static function toArray(): array
    {
        return array_map(
            fn($case) => ['label'  => $case->name,
                          'value' => $case->value
            ],
            self::cases()
        );
    }
}