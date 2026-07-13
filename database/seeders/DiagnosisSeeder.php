<?php

namespace Database\Seeders;

use App\Models\Diagnosis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class DiagnosisSeeder extends Seeder
{
    /**
     * Seed the diagnoses catalog from the curated ICD-10 JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $diagnoses = collect(File::json(database_path('data/diagnoses.json')))
            ->map(fn (array $diagnosis): array => [
                'diagnosis' => $diagnosis['diagnosis'],
                'icd10_code' => $diagnosis['icd10_code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $diagnoses->chunk(100)->each(function ($chunk): void {
            Diagnosis::insert($chunk->all());
        });
    }
}
