<?php

use App\Enums\UserRole;
use App\Models\Diagnosis;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

function validDiagnosisPayload(array $overrides = []): array
{
    return array_merge([
        'diagnosis' => 'Essential (primary) hypertension',
        'icd10_code' => 'I10',
    ], $overrides);
}

it('renders the catalog index', function (): void {
    Diagnosis::factory()->create();

    $this->get(route('diagnoses.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Diagnoses/Index')
            ->has('diagnoses.data')
        );
});

it('renders the create form', function (): void {
    $this->get(route('diagnoses.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Diagnoses/Form'));
});

it('stores a new diagnosis', function (): void {
    $this->post(route('diagnoses.store'), validDiagnosisPayload())
        ->assertRedirect(route('diagnoses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('diagnoses', ['diagnosis' => 'Essential (primary) hypertension', 'icd10_code' => 'I10']);
});

it('renders the edit form', function (): void {
    $diagnosis = Diagnosis::factory()->create();

    $this->get(route('diagnoses.edit', $diagnosis))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Diagnoses/Form')
            ->where('diagnosis.id', $diagnosis->id)
        );
});

it('updates a diagnosis', function (): void {
    $diagnosis = Diagnosis::factory()->create();

    $this->put(route('diagnoses.update', $diagnosis), validDiagnosisPayload(['diagnosis' => 'Hypertensive heart disease']))
        ->assertRedirect(route('diagnoses.index'))
        ->assertSessionHas('success');

    expect($diagnosis->fresh()->diagnosis)->toBe('Hypertensive heart disease');
});

it('soft-deletes a diagnosis', function (): void {
    $diagnosis = Diagnosis::factory()->create();

    $this->delete(route('diagnoses.destroy', $diagnosis))
        ->assertRedirect(route('diagnoses.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($diagnosis);
});

it('validates required fields', function (): void {
    $this->post(route('diagnoses.store'), validDiagnosisPayload(['diagnosis' => '', 'icd10_code' => '']))
        ->assertSessionHasErrors(['diagnosis', 'icd10_code']);
});

it('rejects a duplicate icd10 code on create but allows keeping it on update', function (): void {
    Diagnosis::factory()->create(['icd10_code' => 'I10']);

    $this->post(route('diagnoses.store'), validDiagnosisPayload())
        ->assertSessionHasErrors('icd10_code');

    $diagnosis = Diagnosis::factory()->create(['icd10_code' => 'E11.9']);
    $this->put(route('diagnoses.update', $diagnosis), validDiagnosisPayload(['icd10_code' => 'E11.9']))
        ->assertSessionHasNoErrors();
});

it('forbids a user without diagnosis permissions', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('diagnoses.index'))->assertForbidden();
    $this->post(route('diagnoses.store'), validDiagnosisPayload())->assertForbidden();
});
