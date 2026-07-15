<?php

use App\Enums\AllergenCategory;
use App\Enums\UserRole;
use App\Models\Allergen;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);

    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

it('lists the allergen catalog', function (): void {
    Allergen::factory()->create(['name' => 'Penicillin', 'category' => AllergenCategory::Drug]);

    $this->actingAs($this->staff)
        ->get(route('allergens.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Allergens/Index')
            ->has('allergens.data', 1)
            ->has('category_options')
            ->where('allergens.data.0.name', 'Penicillin')
        );
});

it('creates an allergen', function (): void {
    $this->actingAs($this->staff)
        ->post(route('allergens.store'), [
            'name' => 'Latex',
            'category' => AllergenCategory::Environmental->value,
        ])
        ->assertRedirect(route('allergens.index'))
        ->assertSessionHas('success');

    $allergen = Allergen::firstOrFail();

    expect($allergen->name)->toBe('Latex')
        ->and($allergen->category)->toBe(AllergenCategory::Environmental);
});

it('validates the allergen payload', function (array $overrides, string $invalidField): void {
    $payload = array_merge([
        'name' => 'Latex',
        'category' => AllergenCategory::Environmental->value,
    ], $overrides);

    $this->actingAs($this->staff)
        ->post(route('allergens.store'), $payload)
        ->assertSessionHasErrors($invalidField);

    expect(Allergen::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'missing category' => [['category' => ''], 'category'],
    'invalid category' => [['category' => 'Nope'], 'category'],
]);

it('rejects a duplicate allergen within the same category', function (): void {
    Allergen::factory()->create(['name' => 'Penicillin', 'category' => AllergenCategory::Drug]);

    $this->actingAs($this->staff)
        ->post(route('allergens.store'), [
            'name' => 'Penicillin',
            'category' => AllergenCategory::Drug->value,
        ])
        ->assertSessionHasErrors('name');

    expect(Allergen::count())->toBe(1);
});

it('allows the same allergen name in a different category', function (): void {
    Allergen::factory()->create(['name' => 'Latex', 'category' => AllergenCategory::Environmental]);

    $this->actingAs($this->staff)
        ->post(route('allergens.store'), [
            'name' => 'Latex',
            'category' => AllergenCategory::Other->value,
        ])
        ->assertSessionHasNoErrors();

    expect(Allergen::count())->toBe(2);
});

it('updates an allergen', function (): void {
    $allergen = Allergen::factory()->create(['name' => 'Latex', 'category' => AllergenCategory::Other]);

    $this->actingAs($this->staff)
        ->put(route('allergens.update', $allergen), [
            'name' => 'Latex Gloves',
            'category' => AllergenCategory::Environmental->value,
        ])
        ->assertRedirect(route('allergens.index'));

    $allergen->refresh();

    expect($allergen->name)->toBe('Latex Gloves')
        ->and($allergen->category)->toBe(AllergenCategory::Environmental);
});

it('soft deletes an allergen', function (): void {
    $allergen = Allergen::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('allergens.destroy', $allergen))
        ->assertRedirect(route('allergens.index'));

    expect(Allergen::find($allergen->id))->toBeNull()
        ->and(Allergen::withTrashed()->find($allergen->id))->not->toBeNull();
});

it('searches the allergen catalog by name', function (): void {
    Allergen::factory()->create(['name' => 'Penicillin', 'category' => AllergenCategory::Drug]);
    Allergen::factory()->create(['name' => 'Peanuts', 'category' => AllergenCategory::Food]);

    $this->actingAs($this->staff)
        ->getJson(route('allergens.search', ['search' => 'Penicillin']))
        ->assertOk()
        ->assertJsonCount(1, 'allergens')
        ->assertJsonPath('allergens.0.name', 'Penicillin')
        ->assertJsonPath('allergens.0.category', AllergenCategory::Drug->value);
});

it('searches the allergen catalog by category', function (): void {
    Allergen::factory()->create(['name' => 'Penicillin', 'category' => AllergenCategory::Drug]);
    Allergen::factory()->create(['name' => 'Peanuts', 'category' => AllergenCategory::Food]);

    $this->actingAs($this->staff)
        ->getJson(route('allergens.search', ['search' => 'Food']))
        ->assertOk()
        ->assertJsonCount(1, 'allergens')
        ->assertJsonPath('allergens.0.name', 'Peanuts');
});

it('requires authentication to search allergens', function (): void {
    $this->getJson(route('allergens.search', ['search' => 'Penicillin']))
        ->assertUnauthorized();
});

it('denies catalog management to a role without allergen permissions', function (): void {
    $role = Role::findOrCreate('Read Only');
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get(route('allergens.index'))->assertForbidden();
    $this->actingAs($user)->post(route('allergens.store'), [
        'name' => 'Latex',
        'category' => AllergenCategory::Environmental->value,
    ])->assertForbidden();

    expect(Allergen::count())->toBe(0);
});
