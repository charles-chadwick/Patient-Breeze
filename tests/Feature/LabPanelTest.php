<?php

use App\Models\LabOrder;
use App\Models\LabOrderLabPanel;
use App\Models\LabPanel;
use Database\Seeders\LabOrderSeeder;
use Database\Seeders\LabPanelSeeder;

use function Pest\Laravel\seed;

it('groups multiple lab orders into a panel', function (): void {
    $panel = LabPanel::factory()->create(['name' => 'Basic Metabolic Panel (BMP)']);

    $orders = LabOrder::factory()->count(3)->create();
    $panel->labOrders()->sync($orders->pluck('id'));

    expect($panel->labOrders)->toHaveCount(3)
        ->and($orders->first()->fresh()->labPanels->pluck('name'))->toContain('Basic Metabolic Panel (BMP)');
});

it('lets a lab order belong to more than one panel', function (): void {
    $order = LabOrder::factory()->create();

    $first_panel = LabPanel::factory()->create();
    $second_panel = LabPanel::factory()->create();

    $first_panel->labOrders()->attach($order);
    $second_panel->labOrders()->attach($order);

    expect($order->fresh()->labPanels)->toHaveCount(2);
});

it('soft-deletes a panel-to-order link, hiding it while preserving the row', function (): void {
    $panel = LabPanel::factory()->create();
    $order = LabOrder::factory()->create();

    $panel->labOrders()->attach($order);

    $pivot = LabOrderLabPanel::query()
        ->where('lab_panel_id', $panel->id)
        ->where('lab_order_id', $order->id)
        ->firstOrFail();

    $pivot->delete();

    expect($panel->fresh()->labOrders)->toHaveCount(0)
        ->and(LabOrderLabPanel::withTrashed()->count())->toBe(1)
        ->and($pivot->fresh()->deleted_at)->not->toBeNull();
});

it('seeds common panels with their member lab orders attached', function (): void {
    seed(LabOrderSeeder::class);
    seed(LabPanelSeeder::class);

    $bmp = LabPanel::where('name', 'Basic Metabolic Panel (BMP)')->firstOrFail();

    expect(LabPanel::count())->toBeGreaterThanOrEqual(8)
        ->and($bmp->labOrders)->not->toBeEmpty()
        ->and($bmp->labOrders->pluck('name'))->toContain('Glucose, Fasting');
});
