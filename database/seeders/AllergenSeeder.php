<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class AllergenSeeder extends Seeder
{
    /**
     * Seed the allergen catalog from the curated JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $allergens = collect(File::json(database_path('data/allergens.json')))
            ->map(fn (array $allergen): array => [
                'name' => $allergen['name'],
                'category' => $allergen['category'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $allergens->chunk(100)->each(function ($chunk): void {
            Allergen::insert($chunk->all());
        });
    }
}
