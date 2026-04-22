<?php

namespace Database\Seeders;

use App\Models\User;
use Throwable;

trait ReadsCsvData
{
    private function uniqueEmailFor(string $firstName, string $lastName): string
    {
        $base = strtolower("{$firstName}.{$lastName}");
        $email = "{$base}@example.com";
        $suffix = 1;

        while (User::withTrashed()->where('email', $email)->exists()) {
            $email = $base.(++$suffix).'@example.com';
        }

        return $email;
    }

    /**
     * @return list<array<string, string>>
     *
     * @throws Throwable
     */
    private function readCsv(string $path): array
    {
        throw_if(! file_exists($path), new \RuntimeException("CSV file not found: {$path}"));

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        return array_values(array_filter(
            array_map(fn (array $row) => count($row) >= 3 ? array_combine($header, $row) : null, $rows),
            fn (?array $row) => $row !== null,
        ));
    }
}
