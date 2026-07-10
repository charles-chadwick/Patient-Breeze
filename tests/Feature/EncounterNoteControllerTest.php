<?php

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Enums\UserRole;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('stores an encounter note as the author, unsigned', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->post(route('patients.encounter-notes.store', $patient), [
            'type' => EncounterNoteType::Progress->value,
            'encounter_date' => '2026-07-01',
            'title' => 'Initial visit',
            'content' => '<p>Seen today</p>',
        ])
        ->assertRedirect();

    $note = EncounterNote::firstOrFail();
    expect($note->author_id)->toBe($user->id)
        ->and($note->status)->toBe(EncounterNoteStatus::Unsigned);
});

it('forbids updating a signed note', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($user, 'author')->signed()->create([
        'signed_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('patients.encounter-notes.update', [$note->patient_id, $note]), [
            'type' => EncounterNoteType::Progress->value,
            'encounter_date' => '2026-07-02',
            'title' => 'Changed',
            'content' => '<p>Changed</p>',
        ])
        ->assertForbidden();
});

it('signs a note as the author and co-signs as a different user', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $other = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    $this->actingAs($author)
        ->post(route('patients.encounter-notes.sign', [$note->patient_id, $note]))
        ->assertRedirect();
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Signed);

    $this->actingAs($other)
        ->post(route('patients.encounter-notes.co-sign', [$note->patient_id, $note]))
        ->assertRedirect();
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::CoSigned);
});

it('forbids co-signing by the signer', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    $this->actingAs($author)
        ->post(route('patients.encounter-notes.co-sign', [$note->patient_id, $note]))
        ->assertForbidden();
});

it('audit-logs the sign event', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    expect(Activity::forSubject($note)->where('description', 'signed')->count())->toBe(0);

    app(SignEncounterNoteAction::class)->execute($note, $author);

    expect(Activity::forSubject($note)->where('description', 'signed')->count())->toBe(1);

    $activity = Activity::forSubject($note)->where('description', 'signed')->first();
    expect($activity->causer_id)->toBe($author->id);
});

it('audit-logs the co-sign event', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $coSigner = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    expect(Activity::forSubject($note)->where('description', 'co_signed')->count())->toBe(0);

    app(CoSignEncounterNoteAction::class)->execute($note, $coSigner);

    expect(Activity::forSubject($note)->where('description', 'co_signed')->count())->toBe(1);

    $activity = Activity::forSubject($note)->where('description', 'co_signed')->first();
    expect($activity->causer_id)->toBe($coSigner->id);
});

it('exposes encounter note props on the patient chart', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('encounter_note_types')
            ->has('patient_appointments')
        );
});
