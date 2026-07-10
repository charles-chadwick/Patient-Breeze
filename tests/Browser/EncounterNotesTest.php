<?php

use App\Enums\EncounterNoteStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a doctor can create and sign an encounter note through the chart UI', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // The type/appointment fields are both <select> elements, so the type
    // select is targeted via a `:has(option[value=...])` selector rather than
    // a bare `select`, which would be ambiguous. The encounter_date field is
    // a reka-ui DatePicker (button trigger + popover calendar, not a plain
    // input); its trigger renders `data-reka-date-field-segment="trigger"`
    // (see node_modules/reka-ui DatePickerTrigger.js) and the calendar marks
    // today's cell with a literal `data-today` attribute (see
    // CalendarCellTrigger.js), so both are targeted directly rather than via
    // translated placeholder text (i18n renders raw keys in the test env, as
    // already noted in NotesTabTest). Content is the Quill editor
    // (`.ql-editor`, a contenteditable div), which Playwright's fill()
    // supports directly.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="patient-tab-encounters"]')
        ->click('[data-testid="new-encounter-note-button"]')
        ->assertVisible('#encounter-note-form')
        ->select('#encounter-note-form select:has(option[value="Progress"])', 'Progress')
        ->click('[data-reka-date-field-segment="trigger"]')
        ->click('[data-today]')
        ->keys('#encounter-note-form', 'Escape')
        ->type('#encounter-note-form input[type="text"]:not([data-link])', 'Initial visit note')
        ->type('.ql-editor', 'Seen today, patient reports improvement.')
        ->click('button[type="submit"][form="encounter-note-form"]')
        ->assertNoJavascriptErrors();

    // The created note is Unsigned and can be signed by its author.
    $page->click('[data-testid="encounter-note-sign"]')
        ->assertNoJavascriptErrors();

    $note = $patient->encounterNotes()->firstOrFail();

    expect($patient->encounterNotes()->count())->toBe(1)
        ->and($note->status)->toBe(EncounterNoteStatus::Signed);
})->group('browser');
