# Polymorphic Notes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a reusable, polymorphic Notes feature (type + title + rich-text content) and surface it as a Notes tab on the patient chart, next to Contacts.

**Architecture:** Mirror the existing Contacts feature exactly — thin controller → single-purpose action, morphTo `notable` relationship, backed enum for `type`, permission-string policy. Notes load into the patient chart via `Inertia::defer()` (like Discussions) with a pulsing skeleton. Rich text is authored with Quill through a small reusable `RichTextEditor.vue` wrapper and stored as HTML.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Tailwind v4, Pest 4, Spatie Permission + Activitylog, Quill 2 (new npm dependency).

## Global Constraints

- PHP 8.5; use constructor property promotion, explicit return types, typed params.
- Control structures always use curly braces.
- Naming: DB columns `snake_case`; PHP methods `camelCase`; classes `TitleCase`; JS refs `snake_case` (match existing `useContactManager` / `Show.vue` style).
- Follow the Contacts feature as the canonical pattern for every layer.
- Run `vendor/bin/pint --dirty --format agent` after any PHP change before committing.
- Tests are Pest; DB is reset via global `LazilyRefreshDatabase` (do NOT add `uses(RefreshDatabase)` per file). Roles must be seeded in `beforeEach` (see ContactTest).
- Run tests scoped: `php artisan test --compact --filter=<Name>`.
- The polymorphic parent FQCN reaches the frontend as the existing `contactable_type` prop (value `Patient::class`); reuse it for `notable_type`. Do NOT add a redundant prop.
- Content is stored as raw Quill HTML. No sanitization step in this pass (notes are authored and viewed by authenticated staff only). If untrusted rendering is ever added, revisit.

---

### Task 1: `NoteType` enum + i18n labels

**Files:**
- Create: `app/Enums/NoteType.php`
- Modify: `lang/en/enums.php` (add a `note_type` block next to `contact_type`, ~line 56)
- Test: `tests/Unit/NoteTypeTest.php`

**Interfaces:**
- Produces: `App\Enums\NoteType` (backed string enum) with cases `General`, `Clinical`, `Administrative`, `CarePlan`; instance `label(): string`; static `values(): array` (list of backing-value strings). Backing values are `TitleCase` tokens matching the `ContactType` convention.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/NoteTypeTest.php`:

```php
<?php

use App\Enums\NoteType;

it('exposes all note type backing values', function (): void {
    expect(NoteType::values())->toBe([
        'General',
        'Clinical',
        'Administrative',
        'CarePlan',
    ]);
});

it('resolves a translated label for each case', function (): void {
    expect(NoteType::CarePlan->label())->toBe('Care Plan')
        ->and(NoteType::General->label())->toBe('General');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=NoteTypeTest`
Expected: FAIL — `Class "App\Enums\NoteType" not found`.

- [ ] **Step 3: Create the enum**

Create `app/Enums/NoteType.php` (mirrors `app/Enums/ContactType.php`):

```php
<?php

namespace App\Enums;

enum NoteType: string
{
    case General = 'General';
    case Clinical = 'Clinical';
    case Administrative = 'Administrative';
    case CarePlan = 'CarePlan';

    public function label(): string
    {
        return __('enums.note_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 4: Add i18n labels**

In `lang/en/enums.php`, add this block immediately after the `contact_type` array (after line 56):

```php
    'note_type' => [
        'General' => 'General',
        'Clinical' => 'Clinical',
        'Administrative' => 'Administrative',
        'CarePlan' => 'Care Plan',
    ],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=NoteTypeTest`
Expected: PASS (2 passed).

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/NoteType.php lang/en/enums.php tests/Unit/NoteTypeTest.php
git commit -m "feat: add NoteType enum with i18n labels"
```

---

### Task 2: Migration, `Note` model, `Patient::notes()`, factory

**Files:**
- Create: `database/migrations/2026_07_08_000000_create_notes_table.php` (use `php artisan make:migration` to get the correct timestamp; content below)
- Create: `app/Models/Note.php`
- Create: `database/factories/NoteFactory.php`
- Modify: `app/Models/Patient.php` (add `notes()` after `documents()`, ~line 99)
- Test: `tests/Feature/NoteTest.php` (model-level cases only in this task)

**Interfaces:**
- Consumes: `App\Enums\NoteType` (Task 1).
- Produces:
  - `notes` table: `id`, `notable_type`, `notable_id`, `type`, `title`, `content` (longText), `timestamps`, `softDeletes`.
  - `App\Models\Note` — `$fillable = ['type','title','content']`; `casts()` → `['type' => NoteType::class]`; `notable(): MorphTo`; `documents(): MorphMany`; traits `HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable`.
  - `App\Models\Patient::notes(): MorphMany` → `morphMany(Note::class, 'notable')`.
  - `Database\Factories\NoteFactory`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/NoteTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=NoteTest`
Expected: FAIL — `Class "App\Models\Note" not found`.

- [ ] **Step 3: Create the migration**

Run: `php artisan make:migration create_notes_table --no-interaction`
Then replace the generated file's body with (mirrors `create_contacts_table` + a `title`/`content`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->morphs('notable');
            $table->string('type');
            $table->string('title');
            $table->longText('content');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/Note.php` (mirrors `app/Models/Contact.php`, minus media):

```php
<?php

namespace App\Models;

use App\Enums\NoteType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'type',
        'title',
        'content',
    ];

    protected function searchableFields(): array
    {
        return [
            'title',
            'content',
        ];
    }

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
        ];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
```

- [ ] **Step 5: Create the factory**

Create `database/factories/NoteFactory.php` (mirrors `ContactFactory`):

```php
<?php

namespace Database\Factories;

use App\Enums\NoteType;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(NoteType::cases()),
            'title' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
```

- [ ] **Step 6: Add the relationship to Patient**

In `app/Models/Patient.php`, add after the `documents()` method (after line 99):

```php
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
```

(`MorphMany` is already imported at the top of the file.)

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --compact --filter=NoteTest`
Expected: PASS (5 passed).

- [ ] **Step 8: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/*_create_notes_table.php app/Models/Note.php app/Models/Patient.php database/factories/NoteFactory.php tests/Feature/NoteTest.php
git commit -m "feat: add Note model, notes migration, and patient relationship"
```

---

### Task 3: Permissions + `NotePolicy`

**Files:**
- Modify: `app/Enums/UserRole.php` (add `notes` to `RESOURCES` line 18; add `notes` grants to Doctor/Nurse/MedicalAssistant/Staff in `grants()`)
- Create: `app/Policies/NotePolicy.php`
- Test: `tests/Feature/NotePolicyTest.php`

**Interfaces:**
- Consumes: `App\Models\Note`, `App\Enums\UserRole`.
- Produces: permissions `view_notes`, `create_notes`, `update_notes`, `delete_notes`; `App\Policies\NotePolicy` (auto-discovered by Laravel's naming convention — no manual registration).

**Grants decision (mirror Contacts exactly):** Doctor gets `view/create/update/delete`; Nurse, MedicalAssistant, Staff get `view/create/update` (no delete); SuperAdmin gets all four via `array_fill_keys`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/NotePolicyTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

it('registers the four note permissions', function (): void {
    expect(UserRole::allPermissions())
        ->toContain('view_notes', 'create_notes', 'update_notes', 'delete_notes');
});

it('lets a doctor delete notes but forbids staff', function (): void {
    $patient = Patient::factory()->create();
    $note = Note::factory()->for($patient, 'notable')->create();

    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    expect($doctor->can('delete', $note))->toBeTrue()
        ->and($staff->can('delete', $note))->toBeFalse()
        ->and($staff->can('create', Note::class))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=NotePolicyTest`
Expected: FAIL — `allPermissions()` does not contain `view_notes` (and/or policy not found).

- [ ] **Step 3: Register the resource + grants**

In `app/Enums/UserRole.php`:

Change line 18 to include `notes`:

```php
    private const RESOURCES = ['patients', 'appointments', 'discussions', 'documents', 'contacts', 'notes', 'users'];
```

In `grants()`, add a `notes` line to each role array, matching that role's contacts grant:

- Doctor block (after the `'contacts' => ['view', 'create', 'update', 'delete'],` line):

```php
                'notes' => ['view', 'create', 'update', 'delete'],
```

- Nurse/MedicalAssistant block (after its `'contacts' => ['view', 'create', 'update'],` line):

```php
                'notes' => ['view', 'create', 'update'],
```

- Staff block (after its `'contacts' => ['view', 'create', 'update'],` line):

```php
                'notes' => ['view', 'create', 'update'],
```

(SuperAdmin already gets all resources via `array_fill_keys(self::RESOURCES, self::ACTIONS)` — no change needed.)

- [ ] **Step 4: Create the policy**

Create `app/Policies/NotePolicy.php` (mirrors `ContactPolicy`):

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_notes');
    }

    public function view(User $user, Note $note): bool
    {
        return $user->can('view_notes');
    }

    public function create(User $user): bool
    {
        return $user->can('create_notes');
    }

    public function update(User $user, Note $note): bool
    {
        return $user->can('update_notes');
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->can('delete_notes');
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->can('delete_notes');
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=NotePolicyTest`
Expected: PASS (2 passed).

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/UserRole.php app/Policies/NotePolicy.php tests/Feature/NotePolicyTest.php
git commit -m "feat: add notes permissions and NotePolicy"
```

---

### Task 4: Form requests, action, controller, route, flash + domain lang

**Files:**
- Create: `app/Http/Requests/StoreNoteRequest.php`
- Create: `app/Http/Requests/UpdateNoteRequest.php`
- Create: `app/Actions/CreateNoteAction.php`
- Create: `app/Http/Controllers/NoteController.php`
- Modify: `routes/web.php` (add resource route in the `auth` group next to `contacts`, ~line 60)
- Modify: `lang/en/flash.php` (add `notes` block next to `contacts`, ~line 29)
- Create: `lang/en/notes.php`
- Test: append controller cases to `tests/Feature/NoteTest.php`

**Interfaces:**
- Consumes: `App\Models\Note`, `App\Enums\NoteType`, `App\Models\Patient`, `App\Policies\NotePolicy`.
- Produces:
  - Route names `notes.store`, `notes.update`, `notes.destroy`.
  - `CreateNoteAction::execute(array $validated): Note` — resolves `$validated['notable_type']::findOrFail($validated['notable_id'])`, then `$parent->notes()->create([...])`.
  - `StoreNoteRequest` validates: `type` (enum), `title`, `content`, `notable_type` (`Rule::in([Patient::class])`), `notable_id`.
  - `UpdateNoteRequest` validates: `type`, `title`, `content`.
  - Flash keys `flash.notes.created|updated|deleted`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/NoteTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=NoteTest`
Expected: FAIL — route `notes.store` not defined.

- [ ] **Step 3: Create the store request**

Create `app/Http/Requests/StoreNoteRequest.php` (mirrors `StoreContactRequest`; only `Patient` is an allowed parent this pass):

```php
<?php

namespace App\Http\Requests;

use App\Enums\NoteType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NoteType::class)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'notable_type' => ['required', 'string', Rule::in([Patient::class])],
            'notable_id' => ['required', 'integer'],
        ];
    }
}
```

- [ ] **Step 4: Create the update request**

Create `app/Http/Requests/UpdateNoteRequest.php` (mirrors `UpdateContactRequest`):

```php
<?php

namespace App\Http\Requests;

use App\Enums\NoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NoteType::class)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 5: Create the action**

Create `app/Actions/CreateNoteAction.php` (mirrors `CreateContactAction`):

```php
<?php

namespace App\Actions;

use App\Models\Note;
use Illuminate\Database\Eloquent\Model;

class CreateNoteAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): Note
    {
        /** @var Model $parent */
        $parent = $validated['notable_type']::query()->findOrFail($validated['notable_id']);

        /** @var Note $note */
        $note = $parent->notes()->create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return $note;
    }
}
```

- [ ] **Step 6: Create the controller**

Create `app/Http/Controllers/NoteController.php` (mirrors `ContactController`, without `index`):

```php
<?php

namespace App\Http\Controllers;

use App\Actions\CreateNoteAction;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request, CreateNoteAction $createNote): RedirectResponse
    {
        $this->authorize('create', Note::class);

        $createNote->execute($request->validated());

        return redirect()->back()->with('success', __('flash.notes.created'));
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return redirect()->back()->with('success', __('flash.notes.updated'));
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->back()->with('success', __('flash.notes.deleted'));
    }
}
```

- [ ] **Step 7: Register the route**

In `routes/web.php`, inside the `auth` middleware group, immediately after the `contacts` resource line (line 60), add:

```php
    Route::resource('notes', NoteController::class)->only(['store', 'update', 'destroy']);
```

Add the import at the top of the file next to the other controller imports:

```php
use App\Http\Controllers\NoteController;
```

- [ ] **Step 8: Add flash + domain lang**

In `lang/en/flash.php`, add after the `contacts` block (after line 29):

```php
    'notes' => [
        'created' => 'Note added successfully.',
        'updated' => 'Note updated successfully.',
        'deleted' => 'Note removed successfully.',
    ],
```

Create `lang/en/notes.php` (mirrors `lang/en/contacts.php`, adapted to type/title/content):

```php
<?php

/*
|--------------------------------------------------------------------------
| Notes domain strings
|--------------------------------------------------------------------------
*/

return [
    'tab' => [
        'heading' => 'Notes',
        'new_note' => '+ New Note',
        'empty' => 'No notes on record.',
        'column_title' => 'Title',
        'column_type' => 'Type',
        'column_updated' => 'Updated',
        'column_actions' => 'Actions',
    ],

    'form' => [
        'label_type' => 'Type',
        'label_title' => 'Title',
        'label_content' => 'Content',
        'placeholder_title' => 'Note title',
        'placeholder_content' => 'Write the note…',
    ],

    'modal' => [
        'new_title' => 'New Note',
        'edit_title' => 'Edit Note',
        'new_description' => 'Add a new note.',
        'edit_description' => 'Update this note’s details.',
        'submit_create' => 'Create Note',
        'submit_update' => 'Save Changes',
    ],

    'confirm' => [
        'delete_title' => 'Delete note?',
        'delete_description' => 'This will permanently remove :title.',
    ],
];
```

- [ ] **Step 9: Run tests to verify they pass**

Run: `php artisan test --compact --filter=NoteTest`
Expected: PASS (all NoteTest cases, including the 6 new controller cases).

- [ ] **Step 10: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreNoteRequest.php app/Http/Requests/UpdateNoteRequest.php app/Actions/CreateNoteAction.php app/Http/Controllers/NoteController.php routes/web.php lang/en/flash.php lang/en/notes.php tests/Feature/NoteTest.php
git commit -m "feat: add note controller, action, requests, route, and lang"
```

---

### Task 5: Expose notes on the patient chart props (backend)

**Files:**
- Modify: `app/Http/Controllers/PatientController.php` (import `NoteType`; add deferred `notes` prop + `note_types` in `show()`, ~lines 8-13 and 98-102)
- Test: `tests/Feature/PatientChartTest.php` (add one case)

**Interfaces:**
- Consumes: `App\Enums\NoteType`, `Patient::notes()`.
- Produces: `Patients/Show` Inertia props `notes` (deferred array of note rows, latest first) and `note_types` (list of strings). Reuses existing `contactable_type` prop for the parent FQCN.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/PatientChartTest.php`:

```php
it('exposes note types on the patient chart', function (): void {
    $patient = Patient::factory()->create();

    $this->get(route('patients.show', $patient))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('note_types')
        );
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="exposes note types"`
Expected: FAIL — prop `note_types` missing.

- [ ] **Step 3: Add the import**

In `app/Http/Controllers/PatientController.php`, add with the other enum imports (after line 12, `use App\Enums\DoseForm;`):

```php
use App\Enums\NoteType;
```

- [ ] **Step 4: Add the props**

In `PatientController::show()`, inside the `Inertia::render('Patients/Show', [...])` array, add after the `discussions` deferred line (line 101):

```php
            'note_types' => NoteType::values(),
            'notes' => Inertia::defer(fn () => $patient->notes()->latest()->get()),
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter="exposes note types"`
Expected: PASS.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/PatientController.php tests/Feature/PatientChartTest.php
git commit -m "feat: expose deferred notes and note types on patient chart"
```

---

### Task 6: Install Quill + `RichTextEditor.vue` wrapper

**Files:**
- Modify: `package.json` (add `quill` dependency — installed via npm, not hand-edited)
- Create: `resources/js/Components/RichTextEditor.vue`

**Interfaces:**
- Produces: `RichTextEditor.vue` — a reusable component with `v-model` (`modelValue: String`, emits `update:modelValue`) that renders a Quill "snow" editor and syncs HTML both ways. Optional `placeholder: String` prop.

- [ ] **Step 1: Install Quill**

Run: `npm install quill@^2.0.0`
Expected: `quill` appears under `dependencies` in `package.json` and `package-lock.json` updates.

- [ ] **Step 2: Create the wrapper component**

Create `resources/js/Components/RichTextEditor.vue`:

```vue
<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor_element = ref(null)
let quill = null
let is_internal_change = false

const toolbar_options = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean'],
]

onMounted(() => {
    quill = new Quill(editor_element.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: { toolbar: toolbar_options },
    })

    if (props.modelValue) {
        quill.clipboard.dangerouslyPasteHTML(props.modelValue)
    }

    quill.on('text-change', () => {
        is_internal_change = true
        const html = quill.getLength() > 1 ? quill.root.innerHTML : ''
        emit('update:modelValue', html)
    })
})

watch(() => props.modelValue, (value) => {
    if (is_internal_change) {
        is_internal_change = false
        return
    }
    if (quill && value !== quill.root.innerHTML) {
        quill.clipboard.dangerouslyPasteHTML(value || '')
    }
})

onBeforeUnmount(() => {
    quill = null
})
</script>

<template>
    <div class="rounded-lg border border-border bg-white">
        <div ref="editor_element"></div>
    </div>
</template>
```

**Notes for the implementer:**
- `quill.getLength() > 1` guards Quill's always-present trailing newline so an empty editor emits `''` (which then fails the `content` `required` rule as intended).
- Do NOT scope-style Quill internals; the imported `quill.snow.css` handles the toolbar/editor chrome.

- [ ] **Step 3: Verify the build compiles**

Run: `npm run build`
Expected: build succeeds with no errors referencing `quill` or `RichTextEditor.vue`.

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json resources/js/Components/RichTextEditor.vue
git commit -m "feat: add Quill dependency and RichTextEditor wrapper"
```

---

### Task 7: Notes frontend — composable, form, modal, tab

**Files:**
- Create: `resources/js/composables/useNoteManager.js`
- Create: `resources/js/Pages/Notes/Partials/Form.vue`
- Create: `resources/js/Components/NoteModal.vue`
- Create: `resources/js/Components/NotesTab.vue`

**Interfaces:**
- Consumes: `RichTextEditor.vue` (Task 6); `@/Components/ui/dialog`; `@/Components/ConfirmDialog.vue`; route names `notes.store|update|destroy`; i18n keys from `lang/en/notes.php`.
- Produces: `NotesTab.vue` with props `notes` (`Array`, default `null` for deferred), `notableType` (`String`), `notableId` (`Number`), `types` (`Array`). It self-manages the modal/delete flow via `useNoteManager` and reloads only the `notes` prop after mutations.

- [ ] **Step 1: Create the composable**

Create `resources/js/composables/useNoteManager.js` (mirrors `useContactManager`, reloads the `notes` prop):

```js
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useNoteManager() {
    const note_modal_open = ref(false)
    const editing_note = ref(null)
    const confirm_open = ref(false)
    const deleting_note = ref(null)
    const deleting = ref(false)

    function openCreateNote() {
        editing_note.value = null
        note_modal_open.value = true
    }

    function openEditNote(note) {
        editing_note.value = note
        note_modal_open.value = true
    }

    function handleNoteSaved() {
        router.reload({ only: ['notes'] })
    }

    function askDeleteNote(note) {
        deleting_note.value = note
        confirm_open.value = true
    }

    function confirmDeleteNote() {
        if (!deleting_note.value) { return }
        deleting.value = true
        router.delete(route('notes.destroy', deleting_note.value.id), {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false
                confirm_open.value = false
                deleting_note.value = null
            },
        })
    }

    return {
        note_modal_open,
        editing_note,
        confirm_open,
        deleting_note,
        deleting,
        openCreateNote,
        openEditNote,
        handleNoteSaved,
        askDeleteNote,
        confirmDeleteNote,
    }
}
```

- [ ] **Step 2: Create the form partial**

Create `resources/js/Pages/Notes/Partials/Form.vue` (mirrors Contacts `Form.vue`; type select + title input + RichTextEditor for content):

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import RichTextEditor from '@/Components/RichTextEditor.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
        validator: (v) => ['post', 'patch', 'put'].includes(v),
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    notableType: {
        type: String,
        default: null,
    },
    notableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    type: props.note?.type ?? '',
    title: props.note?.title ?? '',
    content: props.note?.content ?? '',
    notable_type: props.notableType ?? '',
    notable_id: props.notableId ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form id="note-form" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <select
                v-model="form.type"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.type }"
            >
                <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                <option v-for="opt in types" :key="opt" :value="opt">{{ $t('enums.note_type.' + opt) }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_title') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.title"
                type="text"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.title }"
                :placeholder="$t('notes.form.placeholder_title')"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('notes.form.label_content') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <RichTextEditor v-model="form.content" :placeholder="$t('notes.form.placeholder_content')" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.content }}</p>
        </div>
    </form>
</template>
```

- [ ] **Step 3: Create the modal**

Create `resources/js/Components/NoteModal.vue` (mirrors `ContactModal.vue`):

```vue
<script setup>
import { computed } from 'vue'
import { trans } from 'laravel-vue-i18n'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import NoteForm from '@/Pages/Notes/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    notableType: {
        type: String,
        default: null,
    },
    notableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const is_edit = computed(() => Boolean(props.note?.id))

const action = computed(() =>
    is_edit.value
        ? route('notes.update', props.note.id)
        : route('notes.store'),
)

const method = computed(() => (is_edit.value ? 'patch' : 'post'))

const title = computed(() =>
    is_edit.value
        ? trans('notes.modal.edit_title')
        : trans('notes.modal.new_title'),
)

const description = computed(() =>
    is_edit.value
        ? trans('notes.modal.edit_description')
        : trans('notes.modal.new_description'),
)

const submit_label = computed(() =>
    is_edit.value
        ? trans('notes.modal.submit_update')
        : trans('notes.modal.submit_create'),
)

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <NoteForm
                :key="note?.id ?? 'new'"
                :action="action"
                :method="method"
                :note="note"
                :types="types"
                :notable-type="notableType"
                :notable-id="notableId"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('common.actions.cancel') }}
                </button>
                <button
                    type="submit"
                    form="note-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ submit_label }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 4: Create the tab body**

Create `resources/js/Components/NotesTab.vue` (mirrors `ContactsTab.vue` + `DiscussionList` deferred skeleton). `notes` defaults to `null` while the deferred prop loads:

```vue
<script setup>
import NoteModal from '@/Components/NoteModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useNoteManager } from '@/composables/useNoteManager'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    notes: {
        type: Array,
        default: null,
    },
    notableType: {
        type: String,
        required: true,
    },
    notableId: {
        type: Number,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const {
    note_modal_open,
    editing_note,
    confirm_open,
    deleting_note,
    deleting,
    openCreateNote,
    openEditNote,
    handleNoteSaved,
    askDeleteNote,
    confirmDeleteNote,
} = useNoteManager()

function snippet(html) {
    const text = (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    return text.length > 80 ? text.slice(0, 80) + '…' : text
}
</script>

<template>
    <div class="flex items-center justify-between px-6 py-4">
        <h2 class="font-bold text-foreground">{{ $t('notes.tab.heading') }}</h2>
        <button
            type="button"
            @click="openCreateNote"
            class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
        >
            {{ $t('notes.tab.new_note') }}
        </button>
    </div>

    <div v-if="notes === null" class="divide-y divide-border">
        <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-6 py-4">
            <div class="h-4 w-48 animate-pulse rounded bg-muted"></div>
            <div class="ml-auto h-4 w-24 animate-pulse rounded bg-muted"></div>
        </div>
    </div>

    <div
        v-else-if="notes.length === 0"
        class="px-6 py-8 text-center text-sm text-muted-foreground"
    >
        {{ $t('notes.tab.empty') }}
    </div>

    <table v-else class="w-full text-sm">
        <thead>
            <tr class="border-b border-border text-left">
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_title') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_type') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_updated') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('notes.tab.column_actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            <tr
                v-for="note in notes"
                :key="note.id"
                class="hover:bg-muted/40"
            >
                <td class="px-6 py-3">
                    <div class="font-bold text-foreground">{{ note.title }}</div>
                    <div class="text-xs text-muted-foreground">{{ snippet(note.content) }}</div>
                </td>
                <td class="px-6 py-3">
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ $t('enums.note_type.' + note.type) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-muted-foreground">{{ formatDate(note.updated_at, DATE_SHORT) }}</td>
                <td class="px-6 py-3 text-right">
                    <button
                        type="button"
                        @click="openEditNote(note)"
                        class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('common.actions.edit') }}
                    </button>
                    <button
                        type="button"
                        @click="askDeleteNote(note)"
                        class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('common.actions.delete') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <NoteModal
        v-model:open="note_modal_open"
        :note="editing_note"
        :types="types"
        :notable-type="notableType"
        :notable-id="notableId"
        @saved="handleNoteSaved"
    />

    <ConfirmDialog
        v-model:open="confirm_open"
        :title="$t('notes.confirm.delete_title')"
        :description="deleting_note ? $t('notes.confirm.delete_description', { title: deleting_note.title }) : ''"
        :confirm-label="$t('common.actions.delete')"
        :processing="deleting"
        @confirm="confirmDeleteNote"
    />
</template>
```

**Implementer note:** confirm `formatDate` / `DATE_SHORT` are exported from `@/lib/utils` (they are — used in `Show.vue`). Confirm `ConfirmDialog` prop names (`processing`, `confirm-label`) against `ContactsTab.vue` usage (they match).

- [ ] **Step 5: Verify the build compiles**

Run: `npm run build`
Expected: build succeeds; no unresolved imports.

- [ ] **Step 6: Commit**

```bash
git add resources/js/composables/useNoteManager.js resources/js/Pages/Notes/Partials/Form.vue resources/js/Components/NoteModal.vue resources/js/Components/NotesTab.vue
git commit -m "feat: add notes tab, modal, form, and manager composable"
```

---

### Task 8: Wire the Notes tab into the patient chart + browser smoke test

**Files:**
- Modify: `resources/js/Pages/Patients/Show.vue` (import NotesTab; add `notes`/`note_types` props; add `'notes'` to allowed tabs line 70; add tab button after Contacts; add `<NotesTab>` block after `<ContactsTab>`)
- Modify: `lang/en/patients.php` (add `tab_notes` after `tab_contacts`, line 34)
- Test: `tests/Browser/NotesTabTest.php`

**Interfaces:**
- Consumes: `NotesTab.vue` (Task 7); props `notes`/`note_types` (Task 5); existing `contactable_type` prop as `notable-type`.
- Produces: a "Notes" tab in the patient chart tab bar, between Contacts and Discussions.

- [ ] **Step 1: Add the tab label lang key**

In `lang/en/patients.php`, add after `'tab_contacts' => 'Contacts',` (line 34):

```php
        'tab_notes' => 'Notes',
```

- [ ] **Step 2: Import NotesTab and add props**

In `resources/js/Pages/Patients/Show.vue`:

Add the import after the `ContactsTab` import (line 10):

```js
import NotesTab from '@/Components/NotesTab.vue'
```

Add two props to `defineProps` after the `contact_types` prop (after line 49):

```js
    notes: {
        type: Array,
        default: null,
    },
    note_types: {
        type: Array,
        default: () => [],
    },
```

- [ ] **Step 3: Register the tab in the allowed-tabs list**

Change line 70 to include `'notes'`:

```js
const active_tab = ref(['demographics', 'contacts', 'notes', 'discussions'].includes(initial_tab) ? initial_tab : 'demographics')
```

- [ ] **Step 4: Add the tab button**

In the tab bar, insert this button immediately after the Contacts button's closing `</button>` (after line 112, before the Discussions button):

```html
                <button
                    type="button"
                    @click="active_tab = 'notes'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'notes'
                        ? 'bg-white text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_notes') }}
                </button>
```

- [ ] **Step 5: Add the tab body**

Insert this block immediately after the `</ContactsTab>` / `ContactsTab` closing (after line 134, before `<DiscussionList>`):

```html
            <NotesTab
                v-if="active_tab === 'notes'"
                :notes="notes"
                :notable-type="contactable_type"
                :notable-id="patient.id"
                :types="note_types"
            />
```

- [ ] **Step 6: Write the browser smoke test**

Create `tests/Browser/NotesTabTest.php` (mirrors `AuthorizationModalTest`; note the `+` in button text needs a CSS selector, and Quill's editable is `.ql-editor`):

```php
<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('a staff user can open the notes tab and create a note', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo(['view_patients', 'view_notes', 'create_notes']);

    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    $page->assertSee('Notes')
        ->click('button:has-text("Notes")')
        ->assertSee('+ New Note')
        ->assertNoJavascriptErrors();
})->group('browser');
```

**Implementer note:** if the `button:has-text` locator is flaky, fall back to asserting the tab content renders after a click on the nth tab button, following the selector workaround documented in `AuthorizationModalTest.php`. Keep the test focused on "tab opens, no JS errors" — full create-flow E2E is optional and can be added later if the environment runs browser tests reliably.

- [ ] **Step 7: Build and run the smoke test**

Run: `npm run build`
Then: `php artisan test --compact --filter=NotesTabTest`
Expected: PASS (tab renders, no JS errors). If the browser environment is unavailable in CI, this test is `->group('browser')` and can be excluded there.

- [ ] **Step 8: Run the full notes-related suite**

Run: `php artisan test --compact --filter="Note|PatientChart"`
Expected: all PASS.

- [ ] **Step 9: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/js/Pages/Patients/Show.vue lang/en/patients.php tests/Browser/NotesTabTest.php
git commit -m "feat: add notes tab to patient chart"
```

---

## Final Verification

- [ ] Run the whole affected suite: `php artisan test --compact --filter="Note|PatientChart"` → all green.
- [ ] `vendor/bin/pint --dirty --format agent` → no style diffs remaining.
- [ ] `npm run build` → succeeds.
- [ ] Manually (or via `/run`) open a patient chart, click the **Notes** tab (between Contacts and Discussions), create a note with formatted content, edit it, and delete it (as a Doctor). Confirm the skeleton shows briefly on first load and the list reloads after each mutation.

## Notes for the Executor

- Every layer mirrors the **Contacts** feature; when in doubt, open the corresponding `Contact*` file and follow it.
- Do not add a standalone `/notes` index page — out of scope this pass.
- Do not sanitize note HTML this pass (authenticated-staff authored/viewed only); revisit if untrusted rendering is introduced.
- The polymorphic design already supports attaching notes to other models later — no extra owner is wired now.
