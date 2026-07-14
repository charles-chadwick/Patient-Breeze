<?php

use App\Models\LabOrder;
use Database\Seeders\LabOrderSeeder;

use function Pest\Laravel\seed;

it('seeds the lab order catalog from the JSON dataset', function (): void {
    seed(LabOrderSeeder::class);

    $expected = count(json_decode(file_get_contents(database_path('data/lab_orders.json')), true));

    expect(LabOrder::count())->toBe($expected)
        ->and($expected)->toBeGreaterThanOrEqual(100);
});

it('gives every seeded lab order a name, performing lab, and CPT code', function (): void {
    seed(LabOrderSeeder::class);

    foreach (LabOrder::all() as $lab_order) {
        expect($lab_order->name)->not->toBe('')
            ->and($lab_order->performing_lab)->not->toBe('')
            ->and($lab_order->cpt_code)->not->toBe('');
    }
});

it('builds a valid lab order from the factory', function (): void {
    $lab_order = LabOrder::factory()->create();

    expect($lab_order->name)->not->toBe('')
        ->and($lab_order->performing_lab)->not->toBe('')
        ->and($lab_order->cpt_code)->not->toBe('');
});

it('searches the catalog by name, performing lab, and CPT code', function (): void {
    LabOrder::factory()->create(['name' => 'Hemoglobin A1c', 'performing_lab' => 'Quest Diagnostics', 'cpt_code' => '83036']);
    LabOrder::factory()->create(['name' => 'Lipid Panel', 'performing_lab' => 'Labcorp', 'cpt_code' => '80061']);

    expect(LabOrder::searchCatalog('A1c'))->toHaveCount(1)
        ->and(LabOrder::searchCatalog('Labcorp')->first()['name'])->toBe('Lipid Panel')
        ->and(LabOrder::searchCatalog('80061')->first()['name'])->toBe('Lipid Panel');
});
