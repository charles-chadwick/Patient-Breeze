<?php

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Actions\UnsignEncounterNoteAction;
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

it('stores an encounter note with a chosen owner', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $owner = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->post(route('patients.encounter-notes.store', $patient), [
            'type' => EncounterNoteType::Progress->value,
            'author_id' => $owner->id,
            'encounter_date' => '2026-07-01',
            'title' => 'Initial visit',
            'content' => '<p>Seen today</p>',
        ])
        ->assertRedirect();

    expect(EncounterNote::firstOrFail()->author_id)->toBe($owner->id);
});

it('reassigns the owner when updating a note', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $new_owner = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($user, 'author')->create();

    $this->actingAs($user)
        ->put(route('patients.encounter-notes.update', [$note->patient_id, $note]), [
            'type' => EncounterNoteType::Progress->value,
            'author_id' => $new_owner->id,
            'encounter_date' => '2026-07-02',
            'title' => 'Updated',
            'content' => '<p>Updated</p>',
        ])
        ->assertRedirect();

    expect($note->fresh()->author_id)->toBe($new_owner->id);
});

it('rejects an owner that does not exist', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->post(route('patients.encounter-notes.store', $patient), [
            'type' => EncounterNoteType::Progress->value,
            'author_id' => 999999,
            'encounter_date' => '2026-07-01',
            'title' => 'Initial visit',
            'content' => '<p>Seen today</p>',
        ])
        ->assertInvalid(['author_id']);
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

it('saves and signs a note in one request when the author sets sign', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    $this->actingAs($author)
        ->put(route('patients.encounter-notes.update', [$note->patient_id, $note]), [
            'type' => EncounterNoteType::Progress->value,
            'encounter_date' => '2026-07-02',
            'title' => 'Documented and signed',
            'content' => '<p>Final</p>',
            'sign' => true,
        ])
        ->assertRedirect();

    $note->refresh();
    expect($note->title)->toBe('Documented and signed')
        ->and($note->status)->toBe(EncounterNoteStatus::Signed)
        ->and($note->signed_by)->toBe($author->id);
});

it('saves without signing when sign is omitted', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    $this->actingAs($author)
        ->put(route('patients.encounter-notes.update', [$note->patient_id, $note]), [
            'type' => EncounterNoteType::Progress->value,
            'encounter_date' => '2026-07-02',
            'title' => 'Just saved',
            'content' => '<p>Draft</p>',
        ])
        ->assertRedirect();

    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Unsigned);
});

it('forbids saving and signing a note the user does not author', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $other = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    $this->actingAs($other)
        ->put(route('patients.encounter-notes.update', [$note->patient_id, $note]), [
            'type' => EncounterNoteType::Progress->value,
            'author_id' => $author->id,
            'encounter_date' => '2026-07-02',
            'title' => 'Not my note',
            'content' => '<p>Nope</p>',
            'sign' => true,
        ])
        ->assertForbidden();

    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Unsigned);
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

it('lets the signer unsign a note, reverting it to unsigned and editable', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    $this->actingAs($author)
        ->post(route('patients.encounter-notes.unsign', [$note->patient_id, $note]))
        ->assertRedirect();

    $note->refresh();
    expect($note->status)->toBe(EncounterNoteStatus::Unsigned)
        ->and($note->signed_by)->toBeNull()
        ->and($note->signed_at)->toBeNull()
        ->and($note->isEditable())->toBeTrue();
});

it('clears both signatures when unsigning a co-signed note', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->coSigned()->create([
        'signed_by' => $author->id,
    ]);

    $this->actingAs($author)
        ->post(route('patients.encounter-notes.unsign', [$note->patient_id, $note]))
        ->assertRedirect();

    $note->refresh();
    expect($note->status)->toBe(EncounterNoteStatus::Unsigned)
        ->and($note->signed_by)->toBeNull()
        ->and($note->co_signed_by)->toBeNull()
        ->and($note->co_signed_at)->toBeNull();
});

it('forbids unsigning by a user who is not the signer', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $other = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    $this->actingAs($other)
        ->post(route('patients.encounter-notes.unsign', [$note->patient_id, $note]))
        ->assertForbidden();
});

it('forbids unsigning a note that is already unsigned', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    $this->actingAs($author)
        ->post(route('patients.encounter-notes.unsign', [$note->patient_id, $note]))
        ->assertForbidden();
});

it('audit-logs the unsign event', function () {
    $author = User::factory()->withRole(UserRole::Doctor)->create();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    expect(Activity::forSubject($note)->where('description', 'unsigned')->count())->toBe(0);

    app(UnsignEncounterNoteAction::class)->execute($note, $author);

    expect(Activity::forSubject($note)->where('description', 'unsigned')->count())->toBe(1);

    $activity = Activity::forSubject($note)->where('description', 'unsigned')->first();
    expect($activity->causer_id)->toBe($author->id);
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
            ->has('owner_options')
            ->has('patient_appointments')
        );
});

it('renders the co-signature worklist with only signed notes', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();

    EncounterNote::factory()->signed()->create(['title' => 'Awaiting co-sign']);
    EncounterNote::factory()->create(['title' => 'Draft']); // Unsigned
    EncounterNote::factory()->coSigned()->create(['title' => 'Already co-signed']); // CoSigned

    $this->actingAs($user)
        ->get(route('encounter-notes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('EncounterNotes/Index')
            ->has('notes.data', 1)
            ->where('notes.data.0.title', 'Awaiting co-sign')
        );
});
