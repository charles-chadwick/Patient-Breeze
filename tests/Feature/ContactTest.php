<?php

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Patient;

it('creates a contact belonging to a patient', function (): void {
    $patient = Patient::factory()->create();
    $contact = $patient->contacts()->create([
        'type' => ContactType::Emergency,
        'phone' => '555-0100',
        'street_address' => '123 Main St',
    ]);

    expect($contact->contactable)->toBeInstanceOf(Patient::class)
        ->and($contact->contactable->id)->toBe($patient->id)
        ->and($contact->type)->toBe(ContactType::Emergency)
        ->and($contact->phone)->toBe('555-0100')
        ->and($contact->street_address)->toBe('123 Main St');
});

it('allows phone and street_address to be null', function (): void {
    $patient = Patient::factory()->create();
    $contact = $patient->contacts()->create([
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
    $contact = $patient->contacts()->create(['type' => ContactType::Personal]);

    expect($contact->fresh()->type)->toBe(ContactType::Personal);
});

it('contact factory produces valid contacts', function (): void {
    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();

    expect($contact->type)->toBeInstanceOf(ContactType::class);
});
