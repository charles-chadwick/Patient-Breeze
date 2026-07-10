<?php

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use Tests\TestCase;

uses(TestCase::class);

it('lists all encounter note type values', function () {
    expect(EncounterNoteType::values())->toBe([
        'Progress', 'InitialVisit', 'FollowUp', 'Consultation', 'Procedure', 'DischargeSummary', 'Telephone',
    ]);
});

it('defaults status to unsigned and exposes values', function () {
    expect(EncounterNoteStatus::Unsigned->value)->toBe('Unsigned')
        ->and(EncounterNoteStatus::values())->toBe(['Unsigned', 'Signed', 'CoSigned']);
});

it('translates labels', function () {
    expect(EncounterNoteType::Progress->label())->toBe('Progress Note')
        ->and(EncounterNoteStatus::CoSigned->label())->toBe('Co-signed');
});
