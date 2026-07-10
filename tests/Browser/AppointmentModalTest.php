<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

test('a staff user can edit an appointment via the listing modal', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->create([
        'notes' => 'Original note',
    ]);

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    // Appointments is the default records tab, so the edit button is
    // already visible. The test env renders raw i18n keys rather than
    // translated copy (see EncounterNotesTest), so the modal title
    // appears as "appointments.form.edit_title", not "Edit Appointment".
    // Submitting the modal triggers an async Inertia `put` that
    // redirects back and reloads the appointments list, so wait for the
    // modal to close (the submit button unmounts along with the dialog)
    // before reading the DB, otherwise the assertion can race the request.
    $page->assertNoJavascriptErrors()
        ->click('[data-testid="appointment-edit-button"]')
        ->assertSee('appointments.form.edit_title')
        ->fill('[data-testid="appointment-notes-input"]', 'Updated note')
        ->click('button[type="submit"][form="appointment-form"]')
        ->assertMissing('button[type="submit"][form="appointment-form"]')
        ->assertNoJavascriptErrors();

    expect($appointment->fresh()->notes)->toBe('Updated note');
})->group('browser');
