<?php

use App\Actions\AttachDocumentAction;
use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Storage::fake('public');

    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->staff = User::factory()->withRole(UserRole::Staff)->create();
});

/**
 * Attach a document to a patient's chart as the given uploader.
 */
function attachDocument(Patient $patient, User|Patient $uploader, DocumentType $type = DocumentType::Note): Document
{
    return app(AttachDocumentAction::class)->execute(
        $patient,
        ['type' => $type->value, 'name' => 'Existing Doc', 'document_date' => null, 'notes' => null],
        UploadedFile::fake()->create('existing.pdf', 20, 'application/pdf'),
        $uploader,
    );
}

// --- EHR side ---

it('lets staff upload a document to a patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.documents.store', $patient), [
            'type' => DocumentType::LabResult->value,
            'name' => 'CBC Panel',
            'document_date' => '2026-02-01',
            'notes' => 'Routine bloodwork',
            'file' => UploadedFile::fake()->create('labs.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('patients.show', $patient))
        ->assertSessionHas('success');

    $document = Document::firstOrFail();

    expect($document->documentable->is($patient))->toBeTrue()
        ->and($document->name)->toBe('CBC Panel')
        ->and($document->type)->toBe(DocumentType::LabResult)
        ->and($document->uploader->is($this->staff))->toBeTrue()
        ->and($document->getMedia('file'))->toHaveCount(1);
});

it('defaults the document name to the original file name when omitted', function (): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.documents.store', $patient), [
            'type' => DocumentType::Other->value,
            'file' => UploadedFile::fake()->create('scan.pdf', 40, 'application/pdf'),
        ])
        ->assertRedirect(route('patients.show', $patient));

    expect(Document::firstOrFail()->name)->toBe('scan.pdf');
});

it('validates the upload payload', function (array $payload, string $invalidField): void {
    $patient = Patient::factory()->create();

    $this->actingAs($this->staff)
        ->post(route('patients.documents.store', $patient), $payload)
        ->assertSessionHasErrors($invalidField);

    expect(Document::count())->toBe(0);
})->with([
    'missing file' => [['type' => DocumentType::Note->value], 'file'],
    'missing type' => [['type' => ''], 'type'],
    'invalid type' => [['type' => 'NotARealType'], 'type'],
]);

it('lets staff download any document', function (): void {
    $patient = Patient::factory()->create();
    $document = attachDocument($patient, $patient);

    $this->actingAs($this->staff)
        ->get(route('patients.documents.download', [$patient, $document]))
        ->assertOk()
        ->assertDownload('existing.pdf');
});

it('lets staff delete a document', function (): void {
    $patient = Patient::factory()->create();
    $document = attachDocument($patient, $this->staff);

    $this->actingAs($this->staff)
        ->delete(route('patients.documents.destroy', [$patient, $document]))
        ->assertRedirect(route('patients.show', $patient));

    expect(Document::find($document->id))->toBeNull()
        ->and(Document::withTrashed()->find($document->id))->not->toBeNull();
});

it('scopes the document to its patient in the route binding', function (): void {
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $document = attachDocument($patientA, $this->staff);

    $this->actingAs($this->staff)
        ->get(route('patients.documents.download', [$patientB, $document]))
        ->assertNotFound();
});

it('includes the documents list in the patient chart props', function (): void {
    $patient = Patient::factory()->create();
    attachDocument($patient, $this->staff, DocumentType::Insurance);

    $this->actingAs($this->staff)
        ->get(route('patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('documents', 1)
            ->has('document_type_options')
            ->where('documents.0.type_label', DocumentType::Insurance->label())
        );
});

// --- Portal side ---

it('lets a patient upload their own document', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);

    $this->actingAs($patient, 'portal')
        ->post(route('portal.documents.store'), [
            'type' => DocumentType::Identification->value,
            'file' => UploadedFile::fake()->image('id.png'),
        ])
        ->assertRedirect(route('portal.dashboard'));

    $document = Document::firstOrFail();

    expect($document->documentable->is($patient))->toBeTrue()
        ->and($document->uploader->is($patient))->toBeTrue();
});

it('lets a patient download a document on their own chart', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);
    $document = attachDocument($patient, $this->staff);

    $this->actingAs($patient, 'portal')
        ->get(route('portal.documents.download', $document))
        ->assertOk()
        ->assertDownload('existing.pdf');
});

it('forbids a patient from downloading another patient\'s document', function (): void {
    $owner = Patient::factory()->create(['password' => bcrypt('password')]);
    $other = Patient::factory()->create(['password' => bcrypt('password')]);
    $document = attachDocument($owner, $this->staff);

    $this->actingAs($other, 'portal')
        ->get(route('portal.documents.download', $document))
        ->assertForbidden();
});

it('lets a patient delete a document they uploaded', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);
    $document = attachDocument($patient, $patient);

    $this->actingAs($patient, 'portal')
        ->delete(route('portal.documents.destroy', $document))
        ->assertRedirect(route('portal.dashboard'));

    expect(Document::find($document->id))->toBeNull();
});

it('forbids a patient from deleting a staff-uploaded document', function (): void {
    $patient = Patient::factory()->create(['password' => bcrypt('password')]);
    $document = attachDocument($patient, $this->staff);

    $this->actingAs($patient, 'portal')
        ->delete(route('portal.documents.destroy', $document))
        ->assertForbidden();

    expect(Document::find($document->id))->not->toBeNull();
});

it('forbids a patient from deleting another patient\'s document', function (): void {
    $owner = Patient::factory()->create(['password' => bcrypt('password')]);
    $other = Patient::factory()->create(['password' => bcrypt('password')]);
    $document = attachDocument($owner, $owner);

    $this->actingAs($other, 'portal')
        ->delete(route('portal.documents.destroy', $document))
        ->assertForbidden();
});
