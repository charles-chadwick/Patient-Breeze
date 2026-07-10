<?php

use App\Enums\EncounterNoteStatus;
use App\Models\Appointment;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\EncounterNoteSeeder;

it('seeds encounter notes for existing patients with valid authorship and signing states', function () {
    $providers = User::factory()->count(4)->create();
    $patients = Patient::factory()->count(15)->create();
    $patients->take(10)->each(fn (Patient $patient) => Appointment::factory()->create(['patient_id' => $patient->id]));

    (new EncounterNoteSeeder)->run();

    $notes = EncounterNote::all();

    expect($notes)->not->toBeEmpty();

    $provider_ids = $providers->pluck('id');
    $patient_ids = $patients->pluck('id');

    $notes->each(function (EncounterNote $note) use ($provider_ids, $patient_ids): void {
        expect($patient_ids)->toContain($note->patient_id)
            ->and($provider_ids)->toContain($note->author_id)
            ->and($note->title)->not->toBeEmpty()
            ->and($note->content)->toStartWith('<p>');

        if ($note->status !== EncounterNoteStatus::Unsigned) {
            expect($note->signed_by)->toBe($note->author_id)
                ->and($note->signed_at)->not->toBeNull();
        }

        if ($note->status === EncounterNoteStatus::CoSigned) {
            expect($note->co_signed_by)->not->toBeNull()
                ->and($note->co_signed_by)->not->toBe($note->signed_by);
        }
    });
});

it('only links a note to an appointment belonging to the same patient', function () {
    User::factory()->count(2)->create();
    $patients = Patient::factory()->count(15)->create();
    $patients->each(fn (Patient $patient) => Appointment::factory()->create(['patient_id' => $patient->id]));

    (new EncounterNoteSeeder)->run();

    $notes = EncounterNote::with('appointment')->get();

    expect($notes)->not->toBeEmpty();

    $notes->each(fn (EncounterNote $note) => expect(
        $note->appointment_id === null || $note->appointment->patient_id === $note->patient_id
    )->toBeTrue());
});
