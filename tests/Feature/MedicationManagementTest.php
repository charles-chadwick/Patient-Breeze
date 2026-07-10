<?php

use App\Enums\DoseForm;
use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

function validMedicationPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'Antibiotic',
        'name' => 'Amoxicillin',
        'dosage' => '500mg',
        'dose_form' => DoseForm::Capsule->value,
        'ndc' => '0093-4155-56',
    ], $overrides);
}

it('renders the catalog index', function (): void {
    Medication::factory()->create();

    $this->get(route('medications.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Index')
            ->has('medications.data')
            ->has('dose_form_options')
        );
});

it('renders the create form', function (): void {
    $this->get(route('medications.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Form')
            ->has('dose_form_options')
        );
});

it('stores a new medication', function (): void {
    $this->post(route('medications.store'), validMedicationPayload())
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('medications', ['name' => 'Amoxicillin', 'ndc' => '0093-4155-56']);
});

it('renders the edit form', function (): void {
    $medication = Medication::factory()->create();

    $this->get(route('medications.edit', $medication))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Form')
            ->where('medication.id', $medication->id)
            ->has('dose_form_options')
        );
});

it('updates a medication', function (): void {
    $medication = Medication::factory()->create();

    $this->put(route('medications.update', $medication), validMedicationPayload(['name' => 'Amoxil']))
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    expect($medication->fresh()->name)->toBe('Amoxil');
});

it('soft-deletes a medication', function (): void {
    $medication = Medication::factory()->create();

    $this->delete(route('medications.destroy', $medication))
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($medication);
});

it('validates required fields and dose form', function (): void {
    $this->post(route('medications.store'), validMedicationPayload(['name' => '', 'dose_form' => 'NotAForm']))
        ->assertSessionHasErrors(['name', 'dose_form']);
});

it('rejects a duplicate ndc on create but allows keeping it on update', function (): void {
    Medication::factory()->create(['ndc' => '0093-4155-56']);

    $this->post(route('medications.store'), validMedicationPayload())
        ->assertSessionHasErrors('ndc');

    $medication = Medication::factory()->create(['ndc' => '1111-2222-33']);
    $this->put(route('medications.update', $medication), validMedicationPayload(['ndc' => '1111-2222-33']))
        ->assertSessionHasNoErrors();
});

it('forbids a user without medication permissions', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('medications.index'))->assertForbidden();
    $this->post(route('medications.store'), validMedicationPayload())->assertForbidden();
});
