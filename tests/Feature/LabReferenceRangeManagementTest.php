<?php

use App\Enums\UserRole;
use App\Models\LabOrder;
use App\Models\LabReferenceRange;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

function validLabRangePayload(array $overrides = []): array
{
    return array_merge([
        'gender_at_birth' => 'Female',
        'min_age' => 18,
        'max_age' => null,
        'low_value' => '12.0',
        'high_value' => '15.5',
        'unit' => 'g/dL',
    ], $overrides);
}

it('includes reference ranges and sex options on the edit page', function (): void {
    $labOrder = LabOrder::factory()->create();
    LabReferenceRange::factory()->for($labOrder)->create(['low_value' => '12.0', 'high_value' => '15.5', 'unit' => 'g/dL']);

    $this->get(route('lab-orders.edit', $labOrder))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('LabOrders/Form')
            ->has('reference_ranges', 1)
            ->has('gender_at_birth_options')
            ->where('reference_ranges.0.low_value', '12.0')
            ->where('reference_ranges.0.high_value', '15.5')
        );
});

it('adds a reference range to a lab order', function (): void {
    $labOrder = LabOrder::factory()->create();

    $this->post(route('lab-orders.reference-ranges.store', $labOrder), validLabRangePayload())
        ->assertRedirect(route('lab-orders.edit', $labOrder))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('lab_reference_ranges', [
        'lab_order_id' => $labOrder->id,
        'gender_at_birth' => 'Female',
        'min_age' => 18,
        'low_value' => '12.0',
        'high_value' => '15.5',
        'unit' => 'g/dL',
    ]);
});

it('stores a threshold range with only a high bound', function (): void {
    $labOrder = LabOrder::factory()->create();

    $this->post(route('lab-orders.reference-ranges.store', $labOrder), validLabRangePayload([
        'gender_at_birth' => null,
        'min_age' => null,
        'low_value' => null,
        'high_value' => '200',
        'unit' => 'mg/dL',
    ]))->assertSessionHasNoErrors();

    $range = LabReferenceRange::firstOrFail();

    expect($range->getRawOriginal('low_value'))->toBeNull()
        ->and($range->label())->toBe('< 200 mg/dL');
});

it('updates a reference range', function (): void {
    $labOrder = LabOrder::factory()->create();
    $range = LabReferenceRange::factory()->for($labOrder)->create(['low_value' => '12.0', 'high_value' => '15.5', 'unit' => 'g/dL']);

    $this->put(route('lab-orders.reference-ranges.update', [$labOrder, $range]), validLabRangePayload(['high_value' => '16.0']))
        ->assertRedirect(route('lab-orders.edit', $labOrder))
        ->assertSessionHas('success');

    expect($range->fresh()->getRawOriginal('high_value'))->toBe('16.0');
});

it('soft-deletes a reference range', function (): void {
    $labOrder = LabOrder::factory()->create();
    $range = LabReferenceRange::factory()->for($labOrder)->create();

    $this->delete(route('lab-orders.reference-ranges.destroy', [$labOrder, $range]))
        ->assertRedirect(route('lab-orders.edit', $labOrder))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($range);
});

it('requires a unit and at least one bound', function (): void {
    $labOrder = LabOrder::factory()->create();

    $this->post(route('lab-orders.reference-ranges.store', $labOrder), ['gender_at_birth' => null])
        ->assertSessionHasErrors(['unit', 'low_value', 'high_value']);
});

it('rejects an invalid sex', function (): void {
    $labOrder = LabOrder::factory()->create();

    $this->post(route('lab-orders.reference-ranges.store', $labOrder), validLabRangePayload(['gender_at_birth' => 'Nope']))
        ->assertSessionHasErrors('gender_at_birth');
});

it('scopes the reference range to its lab order in the route binding', function (): void {
    $orderA = LabOrder::factory()->create();
    $orderB = LabOrder::factory()->create();
    $range = LabReferenceRange::factory()->for($orderA)->create();

    $this->put(route('lab-orders.reference-ranges.update', [$orderB, $range]), validLabRangePayload())
        ->assertNotFound();

    $this->delete(route('lab-orders.reference-ranges.destroy', [$orderB, $range]))
        ->assertNotFound();
});

it('forbids a user without lab order permissions', function (): void {
    $labOrder = LabOrder::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->post(route('lab-orders.reference-ranges.store', $labOrder), validLabRangePayload())
        ->assertForbidden();
});
