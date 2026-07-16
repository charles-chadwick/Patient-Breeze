<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vaccine;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

it('lists the vaccine catalog', function (): void {
    Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);

    $this->actingAs($this->staff)
        ->get(route('vaccines.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vaccines/Index')
            ->has('vaccines.data', 1)
            ->where('vaccines.data.0.name', 'Tdap')
            ->where('vaccines.data.0.cvx_code', '115')
        );
});

it('creates a vaccine', function (): void {
    $this->actingAs($this->staff)
        ->post(route('vaccines.store'), [
            'name' => 'Varicella',
            'cvx_code' => '21',
        ])
        ->assertRedirect(route('vaccines.index'))
        ->assertSessionHas('success');

    $vaccine = Vaccine::firstOrFail();

    expect($vaccine->name)->toBe('Varicella')
        ->and($vaccine->cvx_code)->toBe('21');
});

it('validates the vaccine payload', function (array $overrides, string $invalidField): void {
    $payload = array_merge([
        'name' => 'Varicella',
        'cvx_code' => '21',
    ], $overrides);

    $this->actingAs($this->staff)
        ->post(route('vaccines.store'), $payload)
        ->assertSessionHasErrors($invalidField);

    expect(Vaccine::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'missing cvx code' => [['cvx_code' => ''], 'cvx_code'],
    'overlong cvx code' => [['cvx_code' => '12345678901'], 'cvx_code'],
]);

it('rejects a duplicate cvx code', function (): void {
    Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);

    $this->actingAs($this->staff)
        ->post(route('vaccines.store'), [
            'name' => 'Tdap (Boostrix)',
            'cvx_code' => '115',
        ])
        ->assertSessionHasErrors('cvx_code');

    expect(Vaccine::count())->toBe(1);
});

it('updates a vaccine', function (): void {
    $vaccine = Vaccine::factory()->create(['name' => 'Zoster', 'cvx_code' => '121']);

    $this->actingAs($this->staff)
        ->put(route('vaccines.update', $vaccine), [
            'name' => 'Zoster, Recombinant',
            'cvx_code' => '187',
        ])
        ->assertRedirect(route('vaccines.index'));

    $vaccine->refresh();

    expect($vaccine->name)->toBe('Zoster, Recombinant')
        ->and($vaccine->cvx_code)->toBe('187');
});

it('keeps its own cvx code available when updating a vaccine', function (): void {
    $vaccine = Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);

    $this->actingAs($this->staff)
        ->put(route('vaccines.update', $vaccine), [
            'name' => 'Tdap (Adacel)',
            'cvx_code' => '115',
        ])
        ->assertSessionHasNoErrors();

    expect($vaccine->refresh()->name)->toBe('Tdap (Adacel)');
});

it('soft deletes a vaccine', function (): void {
    $vaccine = Vaccine::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('vaccines.destroy', $vaccine))
        ->assertRedirect(route('vaccines.index'));

    expect(Vaccine::find($vaccine->id))->toBeNull()
        ->and(Vaccine::withTrashed()->find($vaccine->id))->not->toBeNull();
});

it('searches the vaccine catalog by name', function (): void {
    Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);
    Vaccine::factory()->create(['name' => 'Varicella', 'cvx_code' => '21']);

    $this->actingAs($this->staff)
        ->getJson(route('vaccines.search', ['search' => 'Varicella']))
        ->assertOk()
        ->assertJsonCount(1, 'vaccines')
        ->assertJsonPath('vaccines.0.name', 'Varicella')
        ->assertJsonPath('vaccines.0.cvx_code', '21');
});

it('searches the vaccine catalog by cvx code', function (): void {
    Vaccine::factory()->create(['name' => 'Tdap', 'cvx_code' => '115']);
    Vaccine::factory()->create(['name' => 'Varicella', 'cvx_code' => '21']);

    $this->actingAs($this->staff)
        ->getJson(route('vaccines.search', ['search' => '115']))
        ->assertOk()
        ->assertJsonCount(1, 'vaccines')
        ->assertJsonPath('vaccines.0.name', 'Tdap');
});

it('requires authentication to search vaccines', function (): void {
    $this->getJson(route('vaccines.search', ['search' => 'Tdap']))
        ->assertUnauthorized();
});

it('denies catalog management to a role without vaccine permissions', function (): void {
    $role = Role::findOrCreate('Read Only');
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get(route('vaccines.index'))->assertForbidden();
    $this->actingAs($user)->post(route('vaccines.store'), [
        'name' => 'Varicella',
        'cvx_code' => '21',
    ])->assertForbidden();

    expect(Vaccine::count())->toBe(0);
});
