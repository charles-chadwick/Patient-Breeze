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
        ->type('.ql-editor', 'Vitals & labs stable.')
        ->click('button[type="submit"][form="encounter-note-form"]')
        ->assertNoJavascriptErrors();

    // The list preview renders decoded plain text, not raw HTML entities. The
    // browser serializes the typed "&" to "&amp;" in the stored content, so a
    // naive tag-strip would surface "&amp;" — assert it is decoded to "&".
    $page->assertSee('Vitals & labs stable.')
        ->assertDontSee('&amp;');

    // The created note is Unsigned and can be signed by its author. Signing
    // triggers an async Inertia partial reload of the `encounter_notes`
    // prop, so wait for the Sign button to disappear (it's rendered only
    // when `can_sign` is true) before reading the DB, otherwise the
    // assertion can race the request.
    $page->click('[data-testid="encounter-note-sign"]')
        ->assertNoJavascriptErrors()
        ->assertMissing('[data-testid="encounter-note-sign"]');

    $note = $patient->encounterNotes()->firstOrFail();

    expect($patient->encounterNotes()->count())->toBe(1)
        ->and($note->status)->toBe(EncounterNoteStatus::Signed);

    // A signed note is no longer editable, so its row exposes a View action
    // instead of Edit. Opening it shows the read-only display (not the form).
    $page->click('[data-testid="encounter-note-view"]')
        ->assertVisible('[data-testid="encounter-note-view-content"]')
        ->assertMissing('#encounter-note-form')
        ->keys('[data-testid="encounter-note-view-content"]', 'Escape')
        ->assertMissing('[data-testid="encounter-note-view-content"]');

    // The signer can revert their own signature. Unsigning reloads the notes,
    // returning the note to Unsigned so the Sign button reappears.
    $page->click('[data-testid="encounter-note-unsign"]')
        ->assertNoJavascriptErrors()
        ->assertVisible('[data-testid="encounter-note-sign"]')
        ->assertMissing('[data-testid="encounter-note-unsign"]');

    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Unsigned);
})->group('browser');
