<?php

use App\Enums\NoteType;
use App\Models\Note;
use App\Models\Patient;
use Database\Seeders\NoteSeeder;

it('seeds notes attached to existing patients with dialogue titles and rich-text bodies', function () {
    $patients = Patient::factory()->count(15)->create();

    (new NoteSeeder)->run();

    $notes = Note::all();

    expect($notes)->not->toBeEmpty();

    $patient_ids = $patients->pluck('id');

    $notes->each(function (Note $note) use ($patient_ids): void {
        expect($note->notable_type)->toBe(Patient::class)
            ->and($patient_ids)->toContain($note->notable_id)
            ->and($note->type)->toBeInstanceOf(NoteType::class)
            ->and($note->title)->not->toBeEmpty()
            ->and($note->content)->toStartWith('<p>');
    });
});
