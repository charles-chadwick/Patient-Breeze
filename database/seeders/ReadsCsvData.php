<?php

namespace Database\Seeders;

use Spatie\MediaLibrary\HasMedia;
use Throwable;

trait ReadsCsvData
{
    /**
     * Attach a seeded avatar image to the model's "avatar" collection, copying
     * the file from database/data/avatars/{directory} so the source is kept.
     */
    private function attachAvatar(HasMedia $model, string $directory, string $file): void
    {
        $path = database_path("data/avatars/{$directory}/{$file}");

        if ($file === '' || ! file_exists($path)) {
            return;
        }

        $model->addMedia($path)->preservingOriginal()->toMediaCollection('avatar');
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
