<?php

namespace Database\Seeders;

use Spatie\MediaLibrary\HasMedia;

trait PreparesSeedData
{
    /**
     * Attach a character's avatar to the model's "avatar" collection. Avatars
     * live in database/data/avatars keyed by character id, and the original
     * file is kept so it can be re-seeded.
     */
    private function attachAvatar(HasMedia $model, int $character_id): void
    {
        $path = database_path("data/avatars/{$character_id}.jpeg");

        if (! file_exists($path)) {
            return;
        }

        $model->addMedia($path)->preservingOriginal()->toMediaCollection('avatar');
    }

    /**
     * Build a sanitized, lower-cased example email address from a full name.
     * Apostrophes are dropped and every other non-alphanumeric run collapses
     * to a single dot, so "Frankenstein's Monster" becomes
     * "frankensteins.monster". An optional suffix (the character id) keeps
     * generated addresses unique.
     */
    private function emailFor(string $name, ?int $suffix = null): string
    {
        $local_part = str($name)
            ->lower()
            ->replace("'", '')
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.');

        if ($suffix !== null) {
            $local_part = $local_part->append('.'.$suffix);
        }

        return $local_part->append('@example.com')->value();
    }
}
