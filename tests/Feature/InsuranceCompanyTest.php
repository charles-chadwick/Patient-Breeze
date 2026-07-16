<?php

use App\Enums\UserRole;
use App\Models\InsuranceCompany;
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

function insuranceCompanyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Blue Cross Blue Shield',
        'payer_id' => 'BCBS01',
        'address_line1' => '123 Main Street',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'phone' => '(800) 555-1000',
        'website' => 'https://example.com',
    ], $overrides);
}

it('lists the insurance company catalog', function (): void {
    InsuranceCompany::factory()->create(['name' => 'Aetna', 'payer_id' => '60054']);

    $this->actingAs($this->staff)
        ->get(route('insurance-companies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('InsuranceCompanies/Index')
            ->has('insurance_companies.data', 1)
            ->where('insurance_companies.data.0.name', 'Aetna')
            ->where('insurance_companies.data.0.payer_id', '60054')
        );
});

it('creates an insurance company', function (): void {
    $this->actingAs($this->staff)
        ->post(route('insurance-companies.store'), insuranceCompanyPayload())
        ->assertRedirect(route('insurance-companies.index'))
        ->assertSessionHas('success');

    $company = InsuranceCompany::firstOrFail();

    expect($company->name)->toBe('Blue Cross Blue Shield')
        ->and($company->payer_id)->toBe('BCBS01')
        ->and($company->city)->toBe('Chicago')
        ->and($company->addressLine())->toContain('123 Main Street');
});

it('validates the insurance company payload', function (array $overrides, string $invalidField): void {
    $this->actingAs($this->staff)
        ->post(route('insurance-companies.store'), insuranceCompanyPayload($overrides))
        ->assertSessionHasErrors($invalidField);

    expect(InsuranceCompany::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
]);

it('rejects a duplicate payer id', function (): void {
    InsuranceCompany::factory()->create(['payer_id' => 'DUP123']);

    $this->actingAs($this->staff)
        ->post(route('insurance-companies.store'), insuranceCompanyPayload(['payer_id' => 'DUP123']))
        ->assertSessionHasErrors('payer_id');
});

it('allows a company with no payer id', function (): void {
    $this->actingAs($this->staff)
        ->post(route('insurance-companies.store'), insuranceCompanyPayload(['payer_id' => '']))
        ->assertSessionHasNoErrors();

    expect(InsuranceCompany::firstOrFail()->payer_id)->toBeNull();
});

it('updates an insurance company', function (): void {
    $company = InsuranceCompany::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->staff)
        ->put(route('insurance-companies.update', $company), insuranceCompanyPayload(['name' => 'New Name']))
        ->assertRedirect(route('insurance-companies.index'));

    expect($company->fresh()->name)->toBe('New Name');
});

it('keeps its own payer id on update', function (): void {
    $company = InsuranceCompany::factory()->create(['payer_id' => 'KEEP01']);

    $this->actingAs($this->staff)
        ->put(route('insurance-companies.update', $company), insuranceCompanyPayload(['payer_id' => 'KEEP01']))
        ->assertSessionHasNoErrors();
});

it('soft deletes an insurance company', function (): void {
    $company = InsuranceCompany::factory()->create();

    $this->actingAs($this->staff)
        ->delete(route('insurance-companies.destroy', $company))
        ->assertRedirect(route('insurance-companies.index'));

    expect(InsuranceCompany::find($company->id))->toBeNull()
        ->and(InsuranceCompany::withTrashed()->find($company->id))->not->toBeNull();
});

it('searches the catalog for the picker', function (): void {
    InsuranceCompany::factory()->create(['name' => 'UnitedHealthcare', 'payer_id' => '87726']);
    InsuranceCompany::factory()->create(['name' => 'Cigna', 'payer_id' => '62308']);

    $this->actingAs($this->staff)
        ->getJson(route('insurance-companies.search', ['search' => 'United']))
        ->assertOk()
        ->assertJsonCount(1, 'insurance_companies')
        ->assertJsonPath('insurance_companies.0.name', 'UnitedHealthcare');
});

it('blocks users without the insurance permission', function (): void {
    $unauthorized = User::factory()->create();
    $unauthorized->syncRoles([]);
    $unauthorized->syncPermissions([]);

    $this->actingAs($unauthorized)
        ->get(route('insurance-companies.index'))
        ->assertForbidden();
});

it('requires authentication', function (): void {
    $this->get(route('insurance-companies.index'))->assertRedirect(route('login'));
});
