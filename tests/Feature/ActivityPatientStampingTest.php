<?php

use App\Models\Contact;
use App\Models\Discussion;
use App\Models\DiscussionParticipant;
use App\Models\Document;
use App\Models\EncounterNote;
use App\Models\Media;
use App\Models\Patient;
use App\Models\PatientMedication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

function latestActivityFor(string $subjectType, int $subjectId): ?Activity
{
    return Activity::where('subject_type', $subjectType)
        ->where('subject_id', $subjectId)
        ->latest('id')
        ->first();
}

it('stamps patient_id on the patient record activity', function (): void {
    $patient = Patient::factory()->create();

    expect(latestActivityFor(Patient::class, $patient->id)?->patient_id)->toBe($patient->id);
});

it('stamps patient_id on a related record with a direct patient_id', function (): void {
    $note = EncounterNote::factory()->create();

    expect(latestActivityFor(EncounterNote::class, $note->id)?->patient_id)->toBe($note->patient_id);
});

it('stamps patient_id on a patient medication', function (): void {
    $med = PatientMedication::factory()->create();

    expect(latestActivityFor(PatientMedication::class, $med->id)?->patient_id)->toBe($med->patient_id);
});

it('stamps patient_id on a patient-scoped discussion via its polymorphic parent', function (): void {
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);

    expect(latestActivityFor(Discussion::class, $discussion->id)?->patient_id)->toBe($patient->id);
});

it('stamps patient_id on a discussion participant via its discussion', function (): void {
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);
    $participant = DiscussionParticipant::factory()->create(['discussion_id' => $discussion->id]);

    expect(latestActivityFor(DiscussionParticipant::class, $participant->id)?->patient_id)->toBe($patient->id);
});

it('stamps patient_id on a discussion participant removed from a soft-deleted discussion', function (): void {
    $patient = Patient::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => Patient::class,
        'discussionable_id' => $patient->id,
    ]);
    $participant = DiscussionParticipant::factory()->create(['discussion_id' => $discussion->id]);

    $discussion->delete();
    $participant->delete();

    expect(latestActivityFor(DiscussionParticipant::class, $participant->id)?->patient_id)->toBe($patient->id);
});

it('leaves patient_id null for a discussion participant on a non-patient discussion', function (): void {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create([
        'discussionable_type' => User::class,
        'discussionable_id' => $user->id,
    ]);
    $participant = DiscussionParticipant::factory()->create(['discussion_id' => $discussion->id]);

    expect(latestActivityFor(DiscussionParticipant::class, $participant->id)?->patient_id)->toBeNull();
});

it('leaves patient_id null for a contact belonging to a user', function (): void {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'contactable_type' => User::class,
        'contactable_id' => $user->id,
    ]);

    expect(latestActivityFor(Contact::class, $contact->id)?->patient_id)->toBeNull();
});

it('leaves patient_id null for a non-patient subject', function (): void {
    $user = User::factory()->create();

    expect(latestActivityFor(User::class, $user->id)?->patient_id)->toBeNull();
});

it('stamps patient_id on media attached directly to a patient', function (): void {
    Storage::fake('public');
    $patient = Patient::factory()->create();

    $media = $patient->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    expect(latestActivityFor(Media::class, $media->id)?->patient_id)->toBe($patient->id);
});

it('stamps patient_id on media attached to a patient document', function (): void {
    Storage::fake('public');
    $patient = Patient::factory()->create();
    $document = Document::factory()->create([
        'documentable_type' => Patient::class,
        'documentable_id' => $patient->id,
    ]);

    $media = $document->addMedia(UploadedFile::fake()->create('labs.pdf', 20, 'application/pdf'))
        ->toMediaCollection('file');

    expect(latestActivityFor(Media::class, $media->id)?->patient_id)->toBe($patient->id);
});

it('leaves patient_id null for media attached to a staff user', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();

    $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    expect(latestActivityFor(Media::class, $media->id))->not->toBeNull()
        ->and(latestActivityFor(Media::class, $media->id)?->patient_id)->toBeNull();
});

it('logs media attributes rather than an empty change set', function (): void {
    Storage::fake('public');
    $patient = Patient::factory()->create();

    $media = $patient->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    $created = Activity::where('subject_type', Media::class)
        ->where('subject_id', $media->id)
        ->where('event', 'created')
        ->sole();
    $attributes = $created->attribute_changes['attributes'] ?? [];

    expect($attributes)->toHaveKeys(['collection_name', 'file_name', 'mime_type', 'size'])
        ->and($attributes['collection_name'])->toBe('avatar')
        ->and($attributes)->not->toHaveKey('custom_properties');
});

it('does not log an empty update when the media library writes its own columns', function (): void {
    Storage::fake('public');
    $patient = Patient::factory()->create();

    $media = $patient->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

    $empty = Activity::where('subject_type', Media::class)
        ->where('subject_id', $media->id)
        ->get()
        ->filter(fn (Activity $activity): bool => ($activity->attribute_changes['attributes'] ?? []) === []);

    expect($empty)->toBeEmpty();
});
