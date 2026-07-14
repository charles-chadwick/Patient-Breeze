<?php

use App\Enums\UserRole;
use App\Models\LabOrder;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

function validLabOrderPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Complete Blood Count (CBC) with Differential',
        'performing_lab' => 'Hospital Core Laboratory',
        'cpt_code' => '85025',
    ], $overrides);
}

it('renders the catalog index', function (): void {
    LabOrder::factory()->create();

    $this->get(route('lab-orders.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('LabOrders/Index')
            ->has('lab_orders.data')
        );
});

it('renders the create form', function (): void {
    $this->get(route('lab-orders.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('LabOrders/Form'));
});

it('stores a new lab order', function (): void {
    $this->post(route('lab-orders.store'), validLabOrderPayload())
        ->assertRedirect(route('lab-orders.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('lab_orders', [
        'name' => 'Complete Blood Count (CBC) with Differential',
        'performing_lab' => 'Hospital Core Laboratory',
        'cpt_code' => '85025',
    ]);
});

it('renders the edit form', function (): void {
    $lab_order = LabOrder::factory()->create();

    $this->get(route('lab-orders.edit', $lab_order))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('LabOrders/Form')
            ->where('lab_order.id', $lab_order->id)
        );
});

it('updates a lab order', function (): void {
    $lab_order = LabOrder::factory()->create();

    $this->put(route('lab-orders.update', $lab_order), validLabOrderPayload(['performing_lab' => 'Labcorp']))
        ->assertRedirect(route('lab-orders.index'))
        ->assertSessionHas('success');

    expect($lab_order->fresh()->performing_lab)->toBe('Labcorp');
});

it('soft-deletes a lab order', function (): void {
    $lab_order = LabOrder::factory()->create();

    $this->delete(route('lab-orders.destroy', $lab_order))
        ->assertRedirect(route('lab-orders.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($lab_order);
});

it('validates required fields', function (): void {
    $this->post(route('lab-orders.store'), validLabOrderPayload(['name' => '', 'performing_lab' => '', 'cpt_code' => '']))
        ->assertSessionHasErrors(['name', 'performing_lab', 'cpt_code']);
});

it('forbids a user without lab order permissions', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('lab-orders.index'))->assertForbidden();
    $this->post(route('lab-orders.store'), validLabOrderPayload())->assertForbidden();
});
