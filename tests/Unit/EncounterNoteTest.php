<?php

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('defaults to an unsigned, editable note', function () {
    $note = EncounterNote::factory()->create();

    expect($note->status)->toBe(EncounterNoteStatus::Unsigned)
        ->and($note->isEditable())->toBeTrue()
        ->and($note->author)->not->toBeNull()
        ->and($note->patient)->not->toBeNull();
});

it('is not editable once signed', function () {
    $note = EncounterNote::factory()->signed()->create();

    expect($note->status)->toBe(EncounterNoteStatus::Signed)
        ->and($note->isEditable())->toBeFalse()
        ->and($note->signer)->not->toBeNull();
});
