<?php

use App\Models\Contact;
use App\Models\Discussion;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\PatientMedication;
use App\Models\User;
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
