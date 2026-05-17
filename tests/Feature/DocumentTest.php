<?php

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());
});

it('creates a document belonging to a patient', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::LabResult,
        'name' => 'CBC Panel',
        'document_date' => '2026-01-15',
        'notes' => 'Routine bloodwork',
    ]);

    expect($document->documentable)->toBeInstanceOf(Patient::class)
        ->and($document->documentable->id)->toBe($patient->id)
        ->and($document->name)->toBe('CBC Panel')
        ->and($document->type)->toBe(DocumentType::LabResult)
        ->and($document->document_date->format('Y-m-d'))->toBe('2026-01-15')
        ->and($document->notes)->toBe('Routine bloodwork');
});

it('creates a document belonging to an appointment', function (): void {
    $appointment = Appointment::factory()->create();
    $document = $appointment->documents()->create([
        'type' => DocumentType::Referral,
        'name' => 'Specialist Referral',
    ]);

    expect($document->documentable)->toBeInstanceOf(Appointment::class)
        ->and($document->documentable->id)->toBe($appointment->id);
});

it('creates a document belonging to a user', function (): void {
    $user = User::factory()->create();
    $document = $user->documents()->create([
        'type' => DocumentType::Certification,
        'name' => 'BLS Certification',
    ]);

    expect($document->documentable)->toBeInstanceOf(User::class)
        ->and($document->documentable->id)->toBe($user->id);
});

it('creates a document belonging to a contact', function (): void {
    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();
    $document = $contact->documents()->create([
        'type' => DocumentType::Consent,
        'name' => 'ROI Form',
    ]);

    expect($document->documentable)->toBeInstanceOf(Contact::class)
        ->and($document->documentable->id)->toBe($contact->id);
});

it('allows document_date and notes to be null', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Note,
        'name' => 'Intake Note',
    ]);

    expect($document->document_date)->toBeNull()
        ->and($document->notes)->toBeNull();
});

it('casts type to DocumentType enum', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Insurance,
        'name' => 'Insurance Card',
    ]);

    expect($document->fresh()->type)->toBe(DocumentType::Insurance);
});

it('soft deletes a document', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Other,
        'name' => 'Miscellaneous',
    ]);

    $document->delete();

    expect(Document::find($document->id))->toBeNull()
        ->and(Document::withTrashed()->find($document->id))->not->toBeNull();
});

it('isolates documents by documentable model', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->create();

    Document::factory()->count(2)->for($patient, 'documentable')->create();
    Document::factory()->count(3)->for($user, 'documentable')->create();

    expect($patient->documents()->count())->toBe(2)
        ->and($user->documents()->count())->toBe(3);
});

it('document factory produces valid documents', function (): void {
    $patient = Patient::factory()->create();
    $document = Document::factory()->for($patient, 'documentable')->create();

    expect($document->type)->toBeInstanceOf(DocumentType::class)
        ->and($document->name)->not->toBeEmpty();
});
