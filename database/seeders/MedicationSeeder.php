<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class MedicationSeeder extends Seeder
{
    /**
     * Seed the medications catalog from the curated JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $medications = collect(File::json(database_path('data/medications.json')))
            ->map(fn (array $medication): array => [
                'type' => $medication['type'],
                'name' => $medication['name'],
                'dosage' => $medication['dosage'],
                'dose_form' => $medication['dose_form'],
                'ndc' => $medication['ndc'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $medications->chunk(100)->each(function ($chunk): void {
            Medication::insert($chunk->all());
        });
    }
}
