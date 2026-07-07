<?php

use App\Enums\ContactType;
use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());
});

it('creates a contact belonging to a patient', function (): void {
    $patient = Patient::factory()->create();
    $contact = $patient->contacts()->create([
        'name' => 'Jane Doe',
        'type' => ContactType::Emergency,
        'phone' => '555-0100',
        'street_address' => '123 Main St',
    ]);

    expect($contact->contactable)->toBeInstanceOf(Patient::class)
        ->and($contact->contactable->id)->toBe($patient->id)
        ->and($contact->name)->toBe('Jane Doe')
        ->and($contact->type)->toBe(ContactType::Emergency)
        ->and($contact->phone)->toBe('555-0100')
        ->and($contact->street_address)->toBe('123 Main St');
});

it('allows phone and street_address to be null', function (): void {
    $patient = Patient::factory()->create();
    $contact = $patient->contacts()->create([
        'name' => 'Jane Doe',
        'type' => ContactType::Work,
    ]);

    expect($contact->phone)->toBeNull()
        ->and($contact->street_address)->toBeNull();
});

it('retrieves all contacts for a patient', function (): void {
    $patient = Patient::factory()->create();
    Contact::factory()->count(3)->for($patient, 'contactable')->create();

    expect($patient->contacts()->count())->toBe(3);
});

it('casts type to ContactType enum', function (): void {
    $patient = Patient::factory()->create();
    $contact = $patient->contacts()->create([
        'name' => 'Jane Doe',
        'type' => ContactType::Personal,
    ]);

    expect($contact->fresh()->type)->toBe(ContactType::Personal);
});

it('contact factory produces valid contacts', function (): void {
    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();

    expect($contact->type)->toBeInstanceOf(ContactType::class)
        ->and($contact->name)->not->toBeEmpty();
});

it('lists contacts on the index page', function (): void {
    $patient = Patient::factory()->create();
    Contact::factory()->count(2)->for($patient, 'contactable')->create();

    $this->get(route('contacts.index'))->assertOk();
});

it('stores a contact via the controller', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('contacts.store'), [
        'name' => 'Jane Doe',
        'type' => ContactType::Emergency->value,
        'phone' => '555-0100',
        'street_address' => '123 Main St',
        'contactable_type' => Patient::class,
        'contactable_id' => $patient->id,
    ])->assertRedirect();

    expect($patient->contacts()->where('name', 'Jane Doe')->exists())->toBeTrue();
});

it('requires name when storing a contact', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('contacts.store'), [
        'type' => ContactType::Emergency->value,
        'contactable_type' => Patient::class,
        'contactable_id' => $patient->id,
    ])->assertSessionHasErrors('name');
});

it('updates a contact via the controller', function (): void {
    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create([
        'name' => 'Old Name',
    ]);

    $this->patch(route('contacts.update', $contact), [
        'name' => 'New Name',
        'type' => $contact->type->value,
        'phone' => $contact->phone,
        'street_address' => $contact->street_address,
    ])->assertRedirect();

    expect($contact->fresh()->name)->toBe('New Name');
});

it('deletes a contact via the controller', function (): void {
    // Deleting contacts requires delete_contacts, which front-desk Staff lack.
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();

    $this->delete(route('contacts.destroy', $contact))->assertRedirect();

    expect(Contact::find($contact->id))->toBeNull();
});
