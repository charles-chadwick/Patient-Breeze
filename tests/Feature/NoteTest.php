<?php

use App\Enums\NoteType;
use App\Enums\UserRole;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Staff)->create());
});

it('creates a note belonging to a patient', function (): void {
    $patient = Patient::factory()->create();
    $note = $patient->notes()->create([
        'type' => NoteType::Clinical,
        'title' => 'Intake summary',
        'content' => '<p>Patient reports mild symptoms.</p>',
    ]);

    expect($note->notable)->toBeInstanceOf(Patient::class)
        ->and($note->notable->id)->toBe($patient->id)
        ->and($note->title)->toBe('Intake summary')
        ->and($note->type)->toBe(NoteType::Clinical)
        ->and($note->content)->toBe('<p>Patient reports mild symptoms.</p>');
});

it('casts type to NoteType enum', function (): void {
    $patient = Patient::factory()->create();
    $note = $patient->notes()->create([
        'type' => NoteType::General,
        'title' => 'Note',
        'content' => '<p>Body</p>',
    ]);

    expect($note->fresh()->type)->toBe(NoteType::General);
});

it('retrieves all notes for a patient', function (): void {
    $patient = Patient::factory()->create();
    Note::factory()->count(3)->for($patient, 'notable')->create();

    expect($patient->notes()->count())->toBe(3);
});

it('soft deletes a note', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    $note->delete();

    expect(Note::find($note->id))->toBeNull()
        ->and(Note::withTrashed()->find($note->id))->not->toBeNull();
});

it('note factory produces valid notes', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    expect($note->type)->toBeInstanceOf(NoteType::class)
        ->and($note->title)->not->toBeEmpty()
        ->and($note->content)->not->toBeEmpty();
});

it('stores a note via the controller', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('notes.store'), [
        'type' => NoteType::Clinical->value,
        'title' => 'Intake summary',
        'content' => '<p>Body text</p>',
        'notable_type' => Patient::class,
        'notable_id' => $patient->id,
    ])->assertRedirect();

    expect($patient->notes()->where('title', 'Intake summary')->exists())->toBeTrue();
});

it('requires title and content when storing a note', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('notes.store'), [
        'type' => NoteType::General->value,
        'notable_type' => Patient::class,
        'notable_id' => $patient->id,
    ])->assertSessionHasErrors(['title', 'content']);
});

it('rejects an invalid note type', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('notes.store'), [
        'type' => 'NotARealType',
        'title' => 'X',
        'content' => '<p>Y</p>',
        'notable_type' => Patient::class,
        'notable_id' => $patient->id,
    ])->assertSessionHasErrors('type');
});

it('rejects a notable_type that is not allow-listed', function (): void {
    $patient = Patient::factory()->create();

    $this->post(route('notes.store'), [
        'type' => NoteType::General->value,
        'title' => 'X',
        'content' => '<p>Y</p>',
        'notable_type' => User::class,
        'notable_id' => $patient->id,
    ])->assertSessionHasErrors('notable_type');
});

it('updates a note via the controller', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create(['title' => 'Old']);

    $this->patch(route('notes.update', $note), [
        'type' => $note->type->value,
        'title' => 'New',
        'content' => '<p>Updated</p>',
    ])->assertRedirect();

    expect($note->fresh()->title)->toBe('New');
});

it('deletes a note via the controller as a doctor', function (): void {
    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());

    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    $this->delete(route('notes.destroy', $note))->assertRedirect();

    expect(Note::find($note->id))->toBeNull();
});

it('forbids staff from deleting a note', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    $this->delete(route('notes.destroy', $note))->assertForbidden();

    expect(Note::find($note->id))->not->toBeNull();
});
