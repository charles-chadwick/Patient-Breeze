<?php

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Patient;
use Database\Seeders\ContactSeeder;

it('seeds contacts attached to existing patients with character names', function () {
    $patients = Patient::factory()->count(15)->create();

    (new ContactSeeder)->run();

    $contacts = Contact::all();

    expect($contacts)->not->toBeEmpty();

    $patient_ids = $patients->pluck('id');

    $contacts->each(function (Contact $contact) use ($patient_ids): void {
        expect($contact->contactable_type)->toBe(Patient::class)
            ->and($patient_ids)->toContain($contact->contactable_id)
            ->and($contact->type)->toBeInstanceOf(ContactType::class)
            ->and($contact->name)->not->toBeEmpty();
    });
});
