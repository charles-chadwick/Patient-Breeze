<?php

use App\Models\Diagnosis;
use Database\Seeders\DiagnosisSeeder;

use function Pest\Laravel\seed;

it('seeds the diagnoses catalog from the JSON dataset', function (): void {
    seed(DiagnosisSeeder::class);

    $expected = count(json_decode(file_get_contents(database_path('data/diagnoses.json')), true));

    expect(Diagnosis::count())->toBe($expected)
        ->and($expected)->toBeGreaterThanOrEqual(250);
});

it('gives every seeded diagnosis a description and ICD-10 code', function (): void {
    seed(DiagnosisSeeder::class);

    foreach (Diagnosis::all() as $diagnosis) {
        expect($diagnosis->diagnosis)->not->toBe('')
            ->and($diagnosis->icd10_code)->toMatch('/^[A-Z]\d/');
    }
});

it('builds a valid diagnosis from the factory', function (): void {
    $diagnosis = Diagnosis::factory()->create();

    expect($diagnosis->diagnosis)->not->toBe('')
        ->and($diagnosis->icd10_code)->not->toBe('');
});

it('searches the catalog by diagnosis and ICD-10 code', function (): void {
    Diagnosis::factory()->create(['diagnosis' => 'Essential hypertension', 'icd10_code' => 'I10']);
    Diagnosis::factory()->create(['diagnosis' => 'Type 2 diabetes mellitus', 'icd10_code' => 'E11.9']);

    expect(Diagnosis::searchCatalog('hypertension'))->toHaveCount(1)
        ->and(Diagnosis::searchCatalog('E11')->first()['diagnosis'])->toBe('Type 2 diabetes mellitus');
});
