# Polymorphic Notes — Design

**Date:** 2026-07-08
**Status:** Approved (pending spec review)

## Summary

A reusable, polymorphic **Notes** feature: each note has a `type`, a `title`, and rich-text
`content`. Notes attach to any model via a `notable` morph. The first consumer is the
**patient chart**, where a new **Notes** tab appears in the top tab bar next to Contacts.

This feature mirrors the existing **Contacts** implementation (thin controller → action,
query-scope/model read methods, morphTo relationship, tab wired into `Patients/Show.vue`)
and borrows the **deferred loading** approach from Discussions.

## Decisions

| Question | Decision |
| --- | --- |
| Rich text editor | **Quill** (new npm dependency) |
| `type` field | **Fixed backed enum** `NoteType`, i18n labels |
| Enum values | `General`, `Clinical`, `Administrative`, `CarePlan` |
| Loading | **Deferred** via `Inertia::defer()`, with skeleton |
| Scope | **Tab-only** — no standalone `/notes` index page this pass |
| Soft deletes + activity logging | **Yes** (match Contacts) |

## Data Model

### Migration: `create_notes_table`

```php
Schema::create('notes', function (Blueprint $table) {
    $table->id();
    $table->morphs('notable');        // notable_type + notable_id
    $table->string('type');           // NoteType enum value
    $table->string('title');
    $table->longText('content');      // Quill HTML
    $table->timestamps();
    $table->softDeletes();
});
```

### Model: `app/Models/Note.php`

- `morphTo notable()`.
- `$fillable = ['type', 'title', 'content']`.
- `casts()` → `['type' => NoteType::class]`.
- Traits: `Searchable`, `Sortable`, `SoftDeletes`, `LogsActivity` (match `Contact`).
- `searchableFields(): array` → `['title', 'content']`.
- `sortableFields(): array` → `['title', 'type', 'created_at']`.

### Owner relationship

`Patient::notes()` → `morphMany(Note::class, 'notable')`.
Polymorphic by design: any other model can gain notes later with a single `morphMany` line.

### Enum: `app/Enums/NoteType.php`

Backed string enum, mirroring `ContactType`:

```php
enum NoteType: string
{
    case General = 'general';
    case Clinical = 'clinical';
    case Administrative = 'administrative';
    case CarePlan = 'care_plan';

    public function label(): string
    {
        return __('enums.note_type.'.$this->value);
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function values(): array { /* ...same shape as ContactType... */ }
}
```

## Backend

### Controller: `app/Http/Controllers/NoteController.php`

Thin, matching `ContactController`. Methods: `store`, `update`, `destroy`.
Each `authorize(...)`, delegates the write, then
`redirect()->back()->with('success', __('flash.notes.*'))`.

### Action: `app/Actions/CreateNoteAction.php`

```php
public function execute(array $validated): Note
{
    $parent = $validated['notable_type']::query()->findOrFail($validated['notable_id']);

    return $parent->notes()->create([
        'type' => $validated['type'],
        'title' => $validated['title'],
        'content' => $validated['content'],
    ]);
}
```

### Form Requests

- `StoreNoteRequest`: `authorize(): true`; rules validate `notable_type`
  (`Rule::in([Patient::class])`), `notable_id`, `type` (`Rule::enum(NoteType::class)`),
  `title` (required string), `content` (required string).
- `UpdateNoteRequest`: own columns only (`type`, `title`, `content`) — no polymorph fields.

### Policy: `app/Policies/NotePolicy.php`

Permission-string based, mirroring `ContactPolicy`: `view_notes`, `create_notes`,
`update_notes`, `delete_notes`; `forceDelete` gated to SuperAdmin.

### Routes: `routes/web.php`

Inside the `auth` group, next to `contacts`:

```php
Route::resource('notes', NoteController::class)->only(['store', 'update', 'destroy']);
```

### Patient chart props: `PatientController::show()`

- Add deferred prop: `'notes' => Inertia::defer(fn () => $patient->notes()->latest()->get())`.
- Add enum options: `'note_types' => NoteType::values()`.
- Reuse the existing patient FQCN as `notable_type` (same `Patient::class` string already
  passed for contacts).

### Factory & Lang

- `database/factories/NoteFactory.php` — random `type`, sentence `title`, paragraph HTML
  `content`.
- `lang/en/notes.php`, `flash.notes.*` keys, and `enums.note_type.*` labels.

## Frontend

### `resources/js/Components/RichTextEditor.vue`

A small reusable wrapper around **Quill**, exposing `v-model` for HTML content. Encapsulates
the editor so it is swappable and reusable beyond notes.

### `resources/js/Components/NotesTab.vue`

The tab body. Header + "New" button; **deferred-load skeleton** (pulsing) while `notes` is
undefined; empty state; list/table of notes (title, type badge, snippet, date) with
Edit/Delete actions. Wired through `useNoteManager`.

### `resources/js/composables/useNoteManager.js`

Reusable state/behavior (mirrors `useContactManager`): modal open state, editing target,
delete-confirm state. `handleNoteSaved()` → `router.reload({ only: ['notes'] })`;
`confirmDeleteNote()` → `router.delete(route('notes.destroy', id), { preserveScroll: true })`.

### `resources/js/Components/NoteModal.vue`

Wraps the shared reka-ui `Dialog`; computes action/method (post vs patch) and title for
create vs edit; renders the Form partial and submits via `form="note-form"`.

### `resources/js/Pages/Notes/Partials/Form.vue`

`useForm(...)` seeded with `notable_type`/`notable_id` from props. Fields: `type` (select
from `note_types`), `title` (input), `content` (`RichTextEditor`). Errors via
`form.errors.<field>`.

### Wire into `resources/js/Pages/Patients/Show.vue`

- Add `'notes'` to the allowed-tabs array (line ~70).
- Add a fourth tab `<button>` next to Contacts.
- Add `<NotesTab v-if="active_tab === 'notes'" :notes="notes" :notable-type="contactable_type" :notable-id="patient.id" :types="note_types" />`.

## New Dependency

- `quill` (npm). Requires approval per CLAUDE.md — granted.

## Testing (Pest feature tests)

- `store`: creates a note against a patient via the polymorphic path; asserts DB row and
  `notable` association; validates auth + validation failures (missing title/content, bad
  enum).
- `update`: edits own columns; asserts no polymorph reassignment.
- `destroy`: soft-deletes; asserts `deleted_at`.
- Policy/permission: a user lacking `*_notes` permission is forbidden.

Use `NoteFactory` and `PatientFactory`. Run with `php artisan test --compact --filter=Note`.

## Out of Scope (this pass)

- Standalone `/notes` index page.
- Attaching notes to models other than `Patient` (the polymorphic design supports it; no
  additional owner wired now).
- Note versioning / history beyond activity log.
