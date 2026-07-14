<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a lab value as a string so any kind of result can be recorded — numeric
 * ("12.5"), threshold ("23"), qualitative ("Negative"), or boolean ("true") — and
 * hydrates it back to its natural PHP type on read, based on what the string holds.
 *
 * @implements CastsAttributes<int|float|bool|string|null, int|float|bool|string|null>
 */
class FlexibleValue implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): int|float|bool|string|null
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        if (is_numeric($value)) {
            $looksLikeFloat = str_contains($value, '.') || str_contains(strtolower($value), 'e');

            return $looksLikeFloat ? (float) $value : (int) $value;
        }

        $normalized = strtolower(trim($value));

        if ($normalized === 'true' || $normalized === 'false') {
            return $normalized === 'true';
        }

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
