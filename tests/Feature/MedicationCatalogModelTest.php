<?php

use App\Models\Medication;
use Illuminate\Http\Request;

it('paginates the catalog keyed by medications sorted by name', function (): void {
    Medication::factory()->create(['name' => 'Zoloft']);
    Medication::factory()->create(['name' => 'Amoxicillin']);

    $result = Medication::listing(new Request);

    expect($result)->toHaveKeys(['medications', 'search', 'sort_by', 'direction', 'filters'])
        ->and($result['medications']->total())->toBe(2)
        ->and($result['medications']->first()->name)->toBe('Amoxicillin');
});

it('filters the catalog by a name search term', function (): void {
    Medication::factory()->create(['name' => 'Lisinopril']);
    Medication::factory()->create(['name' => 'Ibuprofen']);

    $result = Medication::listing(new Request(['search' => 'Lisin']));

    expect($result['medications']->total())->toBe(1)
        ->and($result['medications']->first()->name)->toBe('Lisinopril');
});
