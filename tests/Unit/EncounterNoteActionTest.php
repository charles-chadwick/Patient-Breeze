<?php

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\CreateEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates an unsigned note owned by the author', function () {
    $patient = Patient::factory()->create();
    $author = User::factory()->create();

    $note = app(CreateEncounterNoteAction::class)->execute($patient, $author, [
        'type' => EncounterNoteType::Progress->value,
        'encounter_date' => '2026-07-01',
        'title' => 'Visit',
        'content' => '<p>Notes</p>',
        'appointment_id' => null,
    ]);

    expect($note->author_id)->toBe($author->id)
        ->and($note->patient_id)->toBe($patient->id)
        ->and($note->status)->toBe(EncounterNoteStatus::Unsigned);
});

it('signs then co-signs a note', function () {
    $note = EncounterNote::factory()->create();
    $signer = User::factory()->create();
    $coSigner = User::factory()->create();

    app(SignEncounterNoteAction::class)->execute($note, $signer);
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Signed)
        ->and($note->fresh()->signed_by)->toBe($signer->id);

    app(CoSignEncounterNoteAction::class)->execute($note->fresh(), $coSigner);
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::CoSigned)
        ->and($note->fresh()->co_signed_by)->toBe($coSigner->id);
});
