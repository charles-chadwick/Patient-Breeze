# Patient Encounter Notes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a patient Encounter Notes subsystem — a clinical document (type, encounter date, title, content) with a signing workflow (Unsigned → Signed → Co-signed), separate from the polymorphic `Note`, surfaced in a new "Encounters" chart tab.

**Architecture:** A dedicated `encounter_notes` table with a `belongsTo` patient (+ optional appointment) and author/signer/co-signer users. Two enums (`EncounterNoteType`, `EncounterNoteStatus`). Signing rules live in `EncounterNotePolicy`. Backend mirrors the existing Note/Medication conventions (FormRequests + Actions + a thin controller). Frontend mirrors the Notes tab pattern (deferred prop + Tab + Modal + Form + manager composable).

**Tech Stack:** Laravel 13, Spatie Permission + Activitylog, Inertia v3, Vue 3 `<script setup>`, Tailwind v4, reka-ui dialog, Pest v4.

## Global Constraints

- Local Vue variables `snake_case`; functions `camelCase`; PHP classes `TitleCase`. Curly braces on all control structures; explicit return types & param type hints; constructor property promotion.
- Reuse existing patterns before writing new ones. Mirror `Note`/`NotesTab`/`NoteModal`/`NoteForm`/`useNoteManager`.
- Run `vendor/bin/pint --dirty --format agent` before committing PHP changes.
- Test every change: `php artisan test --compact --filter=...`. Most tests are feature tests using factories.
- No new dependencies. No documentation files. All UI copy via `$t(...)`.
- Status is **never** a user-editable form field — only sign/co-sign actions change it.
- Signing rules: author signs their own note (status Unsigned); co-sign requires a **different** user and status Signed; signed notes are locked (not editable/deletable). Role grants match the `notes` resource.

---

### Task 1: Migration + enums + enum translations

**Files:**
- Create: `database/migrations/2026_07_09_100000_create_encounter_notes_table.php`
- Create: `app/Enums/EncounterNoteType.php`
- Create: `app/Enums/EncounterNoteStatus.php`
- Modify: `lang/en/enums.php`
- Test: `tests/Unit/EncounterNoteEnumTest.php`

**Interfaces:**
- Produces: `EncounterNoteType` (cases Progress, InitialVisit, FollowUp, Consultation, Procedure, DischargeSummary, Telephone) and `EncounterNoteStatus` (Unsigned, Signed, CoSigned), each with `label(): string` and `static values(): array`. `encounter_notes` table columns per the spec.

- [ ] **Step 1: Write the failing enum test**

Create `tests/Unit/EncounterNoteEnumTest.php`:

```php
<?php

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;

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
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter=EncounterNoteEnumTest`
Expected: FAIL — enum classes do not exist.

- [ ] **Step 3: Create `EncounterNoteType`**

Create `app/Enums/EncounterNoteType.php`:

```php
<?php

namespace App\Enums;

enum EncounterNoteType: string
{
    case Progress = 'Progress';
    case InitialVisit = 'InitialVisit';
    case FollowUp = 'FollowUp';
    case Consultation = 'Consultation';
    case Procedure = 'Procedure';
    case DischargeSummary = 'DischargeSummary';
    case Telephone = 'Telephone';

    public function label(): string
    {
        return __('enums.encounter_note_type.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 4: Create `EncounterNoteStatus`**

Create `app/Enums/EncounterNoteStatus.php`:

```php
<?php

namespace App\Enums;

enum EncounterNoteStatus: string
{
    case Unsigned = 'Unsigned';
    case Signed = 'Signed';
    case CoSigned = 'CoSigned';

    public function label(): string
    {
        return __('enums.encounter_note_status.'.$this->value);
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 5: Add enum translations**

In `lang/en/enums.php`, add two new blocks (place them after the `'note_type' => [ ... ],` block):

```php
    'encounter_note_type' => [
        'Progress' => 'Progress Note',
        'InitialVisit' => 'Initial Visit',
        'FollowUp' => 'Follow-up',
        'Consultation' => 'Consultation',
        'Procedure' => 'Procedure',
        'DischargeSummary' => 'Discharge Summary',
        'Telephone' => 'Telephone',
    ],

    'encounter_note_status' => [
        'Unsigned' => 'Unsigned',
        'Signed' => 'Signed',
        'CoSigned' => 'Co-signed',
    ],
```

- [ ] **Step 6: Create the migration**

Create `database/migrations/2026_07_09_100000_create_encounter_notes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->date('encounter_date');
            $table->string('title');
            $table->longText('content');
            $table->string('status')->default('Unsigned');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('co_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('co_signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_notes');
    }
};
```

- [ ] **Step 7: Migrate and run the test**

Run: `php artisan migrate && php artisan test --compact --filter=EncounterNoteEnumTest`
Expected: migration runs; test PASSES.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/EncounterNoteType.php app/Enums/EncounterNoteStatus.php database/migrations/2026_07_09_100000_create_encounter_notes_table.php lang/en/enums.php tests/Unit/EncounterNoteEnumTest.php
git commit -m "feat: add encounter note enums and table"
```

---

### Task 2: `EncounterNote` model, Patient relation, factory

**Files:**
- Create: `app/Models/EncounterNote.php`
- Create: `database/factories/EncounterNoteFactory.php`
- Modify: `app/Models/Patient.php`
- Test: `tests/Unit/EncounterNoteTest.php`

**Interfaces:**
- Consumes: `EncounterNoteType`, `EncounterNoteStatus` (Task 1).
- Produces: `EncounterNote` model with `patient()`, `appointment()`, `author()`, `signer()`, `coSigner()` relations, `$fillable = ['type','encounter_date','title','content','appointment_id']`, casts, and `isEditable(): bool`. `Patient::encounterNotes(): HasMany`. `EncounterNoteFactory` with `unsigned` (default), `signed`, `coSigned` states.

- [ ] **Step 1: Write the failing model test**

Create `tests/Unit/EncounterNoteTest.php`:

```php
<?php

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;

it('defaults to an unsigned, editable note', function () {
    $note = EncounterNote::factory()->create();

    expect($note->status)->toBe(EncounterNoteStatus::Unsigned)
        ->and($note->isEditable())->toBeTrue()
        ->and($note->author)->not->toBeNull()
        ->and($note->patient)->not->toBeNull();
});

it('is not editable once signed', function () {
    $note = EncounterNote::factory()->signed()->create();

    expect($note->status)->toBe(EncounterNoteStatus::Signed)
        ->and($note->isEditable())->toBeFalse()
        ->and($note->signer)->not->toBeNull();
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter=EncounterNoteTest`
Expected: FAIL — model/factory do not exist.

- [ ] **Step 3: Create the model**

Create `app/Models/EncounterNote.php`:

```php
<?php

namespace App\Models;

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Database\Factories\EncounterNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EncounterNote extends Model
{
    /** @use HasFactory<EncounterNoteFactory> */
    use HasFactory, LogsActivity, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'type',
        'encounter_date',
        'title',
        'content',
        'appointment_id',
    ];

    protected function searchableFields(): array
    {
        return ['title', 'content'];
    }

    protected function sortableFields(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
            'encounter_date' => 'encounter_date',
            'status' => 'status',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function coSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'co_signed_by');
    }

    public function isEditable(): bool
    {
        return $this->status === EncounterNoteStatus::Unsigned;
    }

    protected function casts(): array
    {
        return [
            'type' => EncounterNoteType::class,
            'status' => EncounterNoteStatus::class,
            'encounter_date' => 'date',
            'signed_at' => 'datetime',
            'co_signed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
```

- [ ] **Step 4: Add the Patient relation**

In `app/Models/Patient.php`, add an import near the other model imports if `HasMany` isn't already imported (it is — `appointments()` returns `HasMany`). Add this relation method next to `appointments()`:

```php
    public function encounterNotes(): HasMany
    {
        return $this->hasMany(EncounterNote::class);
    }
```

- [ ] **Step 5: Create the factory**

Create `database/factories/EncounterNoteFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EncounterNote>
 */
class EncounterNoteFactory extends Factory
{
    protected $model = EncounterNote::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'author_id' => User::factory(),
            'appointment_id' => null,
            'type' => fake()->randomElement(EncounterNoteType::cases()),
            'encounter_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'title' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => EncounterNoteStatus::Unsigned,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EncounterNoteStatus::Signed,
            'signed_by' => $attributes['author_id'] ?? User::factory(),
            'signed_at' => now(),
        ]);
    }

    public function coSigned(): static
    {
        return $this->signed()->state(fn () => [
            'status' => EncounterNoteStatus::CoSigned,
            'co_signed_by' => User::factory(),
            'co_signed_at' => now(),
        ]);
    }
}
```

- [ ] **Step 6: Run the test**

Run: `php artisan test --compact --filter=EncounterNoteTest`
Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/EncounterNote.php database/factories/EncounterNoteFactory.php app/Models/Patient.php tests/Unit/EncounterNoteTest.php
git commit -m "feat: add EncounterNote model, relation, and factory"
```

---

### Task 3: Permissions + policy

**Files:**
- Modify: `app/Enums/UserRole.php`
- Create: `app/Policies/EncounterNotePolicy.php`
- Test: `tests/Feature/EncounterNotePolicyTest.php`

**Interfaces:**
- Consumes: `EncounterNote`, `EncounterNoteStatus`, `UserRole`.
- Produces: permissions `view/create/update/delete_encounter_notes`; `EncounterNotePolicy` with `viewAny/view/create/update/delete/sign/coSign`. Auto-discovered (`EncounterNote` → `EncounterNotePolicy`).

- [ ] **Step 1: Write the failing policy test**

Create `tests/Feature/EncounterNotePolicyTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\EncounterNote;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
});

function staffUser(): User
{
    return User::factory()->withRole(UserRole::Doctor)->create();
}

it('lets the author sign an unsigned note but forbids a non-author', function () {
    $author = staffUser();
    $other = staffUser();
    $note = EncounterNote::factory()->for($author, 'author')->create();

    expect($author->can('sign', $note))->toBeTrue()
        ->and($other->can('sign', $note))->toBeFalse();
});

it('lets a different user co-sign a signed note but not the signer', function () {
    $author = staffUser();
    $other = staffUser();
    $note = EncounterNote::factory()->for($author, 'author')->signed()->create([
        'signed_by' => $author->id,
    ]);

    expect($other->can('coSign', $note))->toBeTrue()
        ->and($author->can('coSign', $note))->toBeFalse();
});

it('forbids editing a signed note', function () {
    $author = staffUser();
    $unsigned = EncounterNote::factory()->for($author, 'author')->create();
    $signed = EncounterNote::factory()->for($author, 'author')->signed()->create();

    expect($author->can('update', $unsigned))->toBeTrue()
        ->and($author->can('update', $signed))->toBeFalse();
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter=EncounterNotePolicyTest`
Expected: FAIL — permissions/policy missing.

- [ ] **Step 3: Register the resource permissions**

In `app/Enums/UserRole.php`, add `'encounter_notes'` to the `RESOURCES` constant (append after `'notes'`):

```php
    private const RESOURCES = ['patients', 'appointments', 'discussions', 'documents', 'contacts', 'notes', 'encounter_notes', 'users'];
```

Then grant it in `grants()` alongside `notes` in each role branch:
- Doctor: add `'encounter_notes' => ['view', 'create', 'update', 'delete'],`
- Nurse/MedicalAssistant: add `'encounter_notes' => ['view', 'create', 'update'],`
- Staff: add `'encounter_notes' => ['view', 'create', 'update'],`

(SuperAdmin already gets all resources via `array_fill_keys(self::RESOURCES, self::ACTIONS)`.)

- [ ] **Step 4: Create the policy**

Create `app/Policies/EncounterNotePolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class EncounterNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_encounter_notes');
    }

    public function view(User $user, EncounterNote $note): bool
    {
        return $user->can('view_encounter_notes');
    }

    public function create(User $user): bool
    {
        return $user->can('create_encounter_notes');
    }

    public function update(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes') && $note->isEditable();
    }

    public function delete(User $user, EncounterNote $note): bool
    {
        return $user->can('delete_encounter_notes') && $note->isEditable();
    }

    public function sign(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes')
            && $note->status === EncounterNoteStatus::Unsigned
            && $user->id === $note->author_id;
    }

    public function coSign(User $user, EncounterNote $note): bool
    {
        return $user->can('update_encounter_notes')
            && $note->status === EncounterNoteStatus::Signed
            && $user->id !== $note->signed_by;
    }
}
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter=EncounterNotePolicyTest`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/UserRole.php app/Policies/EncounterNotePolicy.php tests/Feature/EncounterNotePolicyTest.php
git commit -m "feat: add encounter note permissions and policy"
```

---

### Task 4: Requests + Actions

**Files:**
- Create: `app/Http/Requests/StoreEncounterNoteRequest.php`
- Create: `app/Http/Requests/UpdateEncounterNoteRequest.php`
- Create: `app/Actions/CreateEncounterNoteAction.php`
- Create: `app/Actions/SignEncounterNoteAction.php`
- Create: `app/Actions/CoSignEncounterNoteAction.php`
- Test: `tests/Unit/EncounterNoteActionTest.php`

**Interfaces:**
- Consumes: `EncounterNote`, `EncounterNoteStatus`, `EncounterNoteType`, `Patient`, `User`.
- Produces:
  - `CreateEncounterNoteAction::execute(Patient $patient, User $author, array $validated): EncounterNote`
  - `SignEncounterNoteAction::execute(EncounterNote $note, User $user): void`
  - `CoSignEncounterNoteAction::execute(EncounterNote $note, User $user): void`
  - `StoreEncounterNoteRequest` rules: type, encounter_date, title, content, nullable appointment_id (scoped to route patient). `UpdateEncounterNoteRequest`: same set.

- [ ] **Step 1: Write the failing action test**

Create `tests/Unit/EncounterNoteActionTest.php`:

```php
<?php

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\CreateEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;

it('creates an unsigned note owned by the author', function () {
    $patient = Patient::factory()->create();
    $author = User::factory()->create();

    $note = app(CreateEncounterNoteAction::class)->execute($patient, $author, [
        'type' => EncounterNoteType::Progress->value,
        'encounter_date' => '2026-07-01',
        'title' => 'Visit',
        'content' => '<p>Notes</p>',
        'appointment_id' => null,
    ]);

    expect($note->author_id)->toBe($author->id)
        ->and($note->patient_id)->toBe($patient->id)
        ->and($note->status)->toBe(EncounterNoteStatus::Unsigned);
});

it('signs then co-signs a note', function () {
    $note = EncounterNote::factory()->create();
    $signer = User::factory()->create();
    $coSigner = User::factory()->create();

    app(SignEncounterNoteAction::class)->execute($note, $signer);
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::Signed)
        ->and($note->fresh()->signed_by)->toBe($signer->id);

    app(CoSignEncounterNoteAction::class)->execute($note->fresh(), $coSigner);
    expect($note->fresh()->status)->toBe(EncounterNoteStatus::CoSigned)
        ->and($note->fresh()->co_signed_by)->toBe($coSigner->id);
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter=EncounterNoteActionTest`
Expected: FAIL — action classes missing.

- [ ] **Step 3: Create `CreateEncounterNoteAction`**

Create `app/Actions/CreateEncounterNoteAction.php`:

```php
<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;

class CreateEncounterNoteAction
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Patient $patient, User $author, array $validated): EncounterNote
    {
        $note = $patient->encounterNotes()->make([
            'type' => $validated['type'],
            'encounter_date' => $validated['encounter_date'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'appointment_id' => $validated['appointment_id'] ?? null,
        ]);

        $note->author_id = $author->id;
        $note->status = EncounterNoteStatus::Unsigned;
        $note->save();

        return $note;
    }
}
```

- [ ] **Step 4: Create `SignEncounterNoteAction`**

Create `app/Actions/SignEncounterNoteAction.php`:

```php
<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class SignEncounterNoteAction
{
    public function execute(EncounterNote $note, User $user): void
    {
        $note->forceFill([
            'status' => EncounterNoteStatus::Signed,
            'signed_by' => $user->id,
            'signed_at' => now(),
        ])->save();
    }
}
```

- [ ] **Step 5: Create `CoSignEncounterNoteAction`**

Create `app/Actions/CoSignEncounterNoteAction.php`:

```php
<?php

namespace App\Actions;

use App\Enums\EncounterNoteStatus;
use App\Models\EncounterNote;
use App\Models\User;

class CoSignEncounterNoteAction
{
    public function execute(EncounterNote $note, User $user): void
    {
        $note->forceFill([
            'status' => EncounterNoteStatus::CoSigned,
            'co_signed_by' => $user->id,
            'co_signed_at' => now(),
        ])->save();
    }
}
```

- [ ] **Step 6: Run the action test**

Run: `php artisan test --compact --filter=EncounterNoteActionTest`
Expected: PASS.

- [ ] **Step 7: Create `StoreEncounterNoteRequest`**

Create `app/Http/Requests/StoreEncounterNoteRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\EncounterNoteType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEncounterNoteRequest extends FormRequest
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
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'type' => ['required', Rule::enum(EncounterNoteType::class)],
            'encounter_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where('patient_id', $patient->id),
            ],
        ];
    }
}
```

- [ ] **Step 8: Create `UpdateEncounterNoteRequest`**

Create `app/Http/Requests/UpdateEncounterNoteRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\EncounterNoteType;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEncounterNoteRequest extends FormRequest
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
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return [
            'type' => ['required', Rule::enum(EncounterNoteType::class)],
            'encounter_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where('patient_id', $patient->id),
            ],
        ];
    }
}
```

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreEncounterNoteRequest.php app/Http/Requests/UpdateEncounterNoteRequest.php app/Actions/CreateEncounterNoteAction.php app/Actions/SignEncounterNoteAction.php app/Actions/CoSignEncounterNoteAction.php tests/Unit/EncounterNoteActionTest.php
git commit -m "feat: add encounter note requests and actions"
```

---

### Task 5: Controller + routes + flash copy

**Files:**
- Create: `app/Http/Controllers/EncounterNoteController.php`
- Modify: `routes/web.php`
- Modify: `lang/en/flash.php`
- Test: `tests/Feature/EncounterNoteControllerTest.php`

**Interfaces:**
- Consumes: the requests and actions from Task 4, `EncounterNotePolicy` (Task 3).
- Produces: routes `patients.encounter-notes.{store,update,destroy,sign,co-sign}`; controller authorizing each action and flashing `flash.encounter_notes.*`.

- [ ] **Step 1: Write the failing controller test**

Create `tests/Feature/EncounterNoteControllerTest.php`:

```php
<?php

use App\Enums\EncounterNoteStatus;
use App\Enums\EncounterNoteType;
use App\Enums\UserRole;
use App\Models\EncounterNote;
use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
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
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter=EncounterNoteControllerTest`
Expected: FAIL — routes/controller missing.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/EncounterNoteController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\CoSignEncounterNoteAction;
use App\Actions\CreateEncounterNoteAction;
use App\Actions\SignEncounterNoteAction;
use App\Http\Requests\StoreEncounterNoteRequest;
use App\Http\Requests\UpdateEncounterNoteRequest;
use App\Models\EncounterNote;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EncounterNoteController extends Controller
{
    public function store(StoreEncounterNoteRequest $request, Patient $patient, CreateEncounterNoteAction $createNote): RedirectResponse
    {
        $this->authorize('create', EncounterNote::class);

        $createNote->execute($patient, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('flash.encounter_notes.created'));
    }

    public function update(UpdateEncounterNoteRequest $request, Patient $patient, EncounterNote $encounterNote): RedirectResponse
    {
        $this->authorize('update', $encounterNote);

        $encounterNote->update($request->validated());

        return redirect()->back()->with('success', __('flash.encounter_notes.updated'));
    }

    public function destroy(Patient $patient, EncounterNote $encounterNote): RedirectResponse
    {
        $this->authorize('delete', $encounterNote);

        $encounterNote->delete();

        return redirect()->back()->with('success', __('flash.encounter_notes.deleted'));
    }

    public function sign(Request $request, Patient $patient, EncounterNote $encounterNote, SignEncounterNoteAction $sign): RedirectResponse
    {
        $this->authorize('sign', $encounterNote);

        $sign->execute($encounterNote, $request->user());

        return redirect()->back()->with('success', __('flash.encounter_notes.signed'));
    }

    public function coSign(Request $request, Patient $patient, EncounterNote $encounterNote, CoSignEncounterNoteAction $coSign): RedirectResponse
    {
        $this->authorize('coSign', $encounterNote);

        $coSign->execute($encounterNote, $request->user());

        return redirect()->back()->with('success', __('flash.encounter_notes.co_signed'));
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add the import near the other controller imports:

```php
use App\Http\Controllers\EncounterNoteController;
```

Inside the same authenticated group that holds the `notes` resource route (after the `Route::resource('notes', ...)` line), add:

```php
    Route::scopeBindings()->group(function (): void {
        Route::post('/patients/{patient}/encounter-notes', [EncounterNoteController::class, 'store'])
            ->name('patients.encounter-notes.store');
        Route::put('/patients/{patient}/encounter-notes/{encounterNote}', [EncounterNoteController::class, 'update'])
            ->name('patients.encounter-notes.update');
        Route::delete('/patients/{patient}/encounter-notes/{encounterNote}', [EncounterNoteController::class, 'destroy'])
            ->name('patients.encounter-notes.destroy');
        Route::post('/patients/{patient}/encounter-notes/{encounterNote}/sign', [EncounterNoteController::class, 'sign'])
            ->name('patients.encounter-notes.sign');
        Route::post('/patients/{patient}/encounter-notes/{encounterNote}/co-sign', [EncounterNoteController::class, 'coSign'])
            ->name('patients.encounter-notes.co-sign');
    });
```

- [ ] **Step 5: Add flash copy**

In `lang/en/flash.php`, add after the `'notes' => [ ... ],` block:

```php
    'encounter_notes' => [
        'created' => 'Encounter note added successfully.',
        'updated' => 'Encounter note updated successfully.',
        'deleted' => 'Encounter note removed successfully.',
        'signed' => 'Encounter note signed.',
        'co_signed' => 'Encounter note co-signed.',
    ],
```

- [ ] **Step 6: Run the controller test**

Run: `php artisan test --compact --filter=EncounterNoteControllerTest`
Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/EncounterNoteController.php routes/web.php lang/en/flash.php tests/Feature/EncounterNoteControllerTest.php
git commit -m "feat: add encounter note controller and routes"
```

---

### Task 6: Expose encounter notes on the patient chart

**Files:**
- Modify: `app/Http/Controllers/PatientController.php` (the `show()` method)
- Test: `tests/Feature/EncounterNoteControllerTest.php` (add one case)

**Interfaces:**
- Consumes: `EncounterNote`, `EncounterNoteType`, the policy.
- Produces: `Patients/Show` props `encounter_notes` (deferred, mapped array with `can_edit`/`can_delete`/`can_sign`/`can_co_sign`), `encounter_note_types`, `patient_appointments`.

- [ ] **Step 1: Write the failing prop test**

Add to `tests/Feature/EncounterNoteControllerTest.php`:

```php
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
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php artisan test --compact --filter="exposes encounter note props"`
Expected: FAIL — props missing.

- [ ] **Step 3: Add imports**

In `app/Http/Controllers/PatientController.php`, add to the enum/model import block:

```php
use App\Enums\EncounterNoteType;
use App\Models\EncounterNote;
```

- [ ] **Step 4: Add the props to `show()`**

Capture the current user near the top of `show()` (after `$this->authorize('view', $patient);`):

```php
        $user = $request->user();
```

Then add these three entries to the `Inertia::render('Patients/Show', [ ... ])` array (place after the existing `'notes' => Inertia::defer(...)` line):

```php
            'encounter_note_types' => EncounterNoteType::values(),
            'patient_appointments' => $patient->appointments()
                ->orderBy('date', 'desc')
                ->get(['id', 'date', 'reason'])
                ->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'date' => $appointment->date->toDateString(),
                    'reason' => $appointment->reason,
                ]),
            'encounter_notes' => Inertia::defer(fn () => $patient->encounterNotes()
                ->with(['author', 'signer', 'coSigner'])
                ->orderBy('encounter_date', 'desc')
                ->get()
                ->map(fn (EncounterNote $note) => [
                    'id' => $note->id,
                    'type' => $note->type->value,
                    'type_label' => $note->type->label(),
                    'encounter_date' => $note->encounter_date->toDateString(),
                    'title' => $note->title,
                    'content' => $note->content,
                    'status' => $note->status->value,
                    'status_label' => $note->status->label(),
                    'appointment_id' => $note->appointment_id,
                    'author_name' => trim("{$note->author->first_name} {$note->author->last_name}"),
                    'signer_name' => $note->signer ? trim("{$note->signer->first_name} {$note->signer->last_name}") : null,
                    'co_signer_name' => $note->coSigner ? trim("{$note->coSigner->first_name} {$note->coSigner->last_name}") : null,
                    'signed_at' => $note->signed_at?->toDateString(),
                    'co_signed_at' => $note->co_signed_at?->toDateString(),
                    'can_edit' => $user->can('update', $note),
                    'can_delete' => $user->can('delete', $note),
                    'can_sign' => $user->can('sign', $note),
                    'can_co_sign' => $user->can('coSign', $note),
                ])),
```

- [ ] **Step 5: Run the test**

Run: `php artisan test --compact --filter="exposes encounter note props"`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/PatientController.php tests/Feature/EncounterNoteControllerTest.php
git commit -m "feat: expose encounter notes on the patient chart"
```

---

### Task 7: Frontend — Encounters tab, modal, form, manager, UI copy

**Files:**
- Create: `resources/js/composables/useEncounterNoteManager.js`
- Create: `resources/js/Pages/EncounterNotes/Partials/Form.vue`
- Create: `resources/js/Components/EncounterNoteModal.vue`
- Create: `resources/js/Components/EncountersTab.vue`
- Modify: `resources/js/Pages/Patients/Show.vue`
- Create: `lang/en/encounter_notes.php`

**Interfaces:**
- Consumes: routes `patients.encounter-notes.*`; props `encounter_notes`, `encounter_note_types`, `patient_appointments`.
- Produces: an **Encounters** tab in the patient chart with create/edit modal, sign/co-sign, and delete.

- [ ] **Step 1: Create the UI copy**

Create `lang/en/encounter_notes.php`:

```php
<?php

return [
    'tab' => [
        'heading' => 'Encounter Notes',
        'new' => 'New Encounter Note',
        'empty' => 'No encounter notes yet.',
    ],
    'columns' => [
        'title' => 'Title',
        'type' => 'Type',
        'encounter_date' => 'Encounter Date',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
    'form' => [
        'label_type' => 'Type',
        'label_encounter_date' => 'Encounter Date',
        'label_title' => 'Title',
        'label_content' => 'Content',
        'label_appointment' => 'Linked Appointment',
        'placeholder_encounter_date' => 'Select date',
        'placeholder_title' => 'Note title',
        'placeholder_content' => 'Document the encounter…',
        'appointment_none' => 'None',
    ],
    'modal' => [
        'new_title' => 'New Encounter Note',
        'edit_title' => 'Edit Encounter Note',
        'new_description' => 'Document a patient encounter.',
        'edit_description' => 'Update this encounter note.',
        'submit_create' => 'Create Note',
        'submit_update' => 'Save Changes',
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'sign' => 'Sign',
        'co_sign' => 'Co-sign',
    ],
    'delete_confirm' => 'Remove this encounter note? This cannot be undone.',
    'signed_by' => 'Signed by :name',
    'co_signed_by' => 'Co-signed by :name',
];
```

- [ ] **Step 2: Create the manager composable**

Create `resources/js/composables/useEncounterNoteManager.js`:

```js
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useEncounterNoteManager(patientId) {
    const modal_open = ref(false)
    const editing_note = ref(null)
    const confirm_open = ref(false)
    const deleting_note = ref(null)
    const deleting = ref(false)

    function openCreate() {
        editing_note.value = null
        modal_open.value = true
    }

    function openEdit(note) {
        editing_note.value = note
        modal_open.value = true
    }

    function handleSaved() {
        router.reload({ only: ['encounter_notes'] })
    }

    function askDelete(note) {
        deleting_note.value = note
        confirm_open.value = true
    }

    function confirmDelete() {
        if (!deleting_note.value) {
            return
        }

        deleting.value = true

        router.delete(route('patients.encounter-notes.destroy', [patientId, deleting_note.value.id]), {
            preserveScroll: true,
            only: ['encounter_notes'],
            onFinish: () => {
                deleting.value = false
                confirm_open.value = false
                deleting_note.value = null
            },
        })
    }

    function sign(note) {
        router.post(route('patients.encounter-notes.sign', [patientId, note.id]), {}, {
            preserveScroll: true,
            only: ['encounter_notes'],
        })
    }

    function coSign(note) {
        router.post(route('patients.encounter-notes.co-sign', [patientId, note.id]), {}, {
            preserveScroll: true,
            only: ['encounter_notes'],
        })
    }

    return {
        modal_open,
        editing_note,
        confirm_open,
        deleting_note,
        deleting,
        openCreate,
        openEdit,
        handleSaved,
        askDelete,
        confirmDelete,
        sign,
        coSign,
    }
}
```

- [ ] **Step 3: Create the form**

Create `resources/js/Pages/EncounterNotes/Partials/Form.vue`:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import DatePicker from '@/Components/ui/DatePicker.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
        validator: (v) => ['post', 'put', 'patch'].includes(v),
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    appointments: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['success'])

const form = useForm({
    type: props.note?.type ?? '',
    encounter_date: props.note?.encounter_date ?? '',
    title: props.note?.title ?? '',
    content: props.note?.content ?? '',
    appointment_id: props.note?.appointment_id ?? '',
})

function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <form id="encounter-note-form" action="#" method="post" @submit.prevent="submit" class="grid gap-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('encounter_notes.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <select
                    v-model="form.type"
                    class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    :class="{ 'border-vibrant-coral-400': form.errors.type }"
                >
                    <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                    <option v-for="opt in types" :key="opt" :value="opt">{{ $t('enums.encounter_note_type.' + opt) }}</option>
                </select>
                <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                    {{ $t('encounter_notes.form.label_encounter_date') }} <span class="text-vibrant-coral-500">*</span>
                </label>
                <DatePicker
                    v-model="form.encounter_date"
                    :placeholder="$t('encounter_notes.form.placeholder_encounter_date')"
                    :class="{ 'ring-2 ring-vibrant-coral-400 rounded-lg': form.errors.encounter_date }"
                />
                <p v-if="form.errors.encounter_date" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.encounter_date }}</p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_title') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <input
                v-model="form.title"
                type="text"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.title }"
                :placeholder="$t('encounter_notes.form.placeholder_title')"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_appointment') }}
            </label>
            <select
                v-model="form.appointment_id"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                :class="{ 'border-vibrant-coral-400': form.errors.appointment_id }"
            >
                <option value="">{{ $t('encounter_notes.form.appointment_none') }}</option>
                <option v-for="appointment in appointments" :key="appointment.id" :value="appointment.id">
                    {{ appointment.date }} · {{ appointment.reason }}
                </option>
            </select>
            <p v-if="form.errors.appointment_id" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.appointment_id }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {{ $t('encounter_notes.form.label_content') }} <span class="text-vibrant-coral-500">*</span>
            </label>
            <RichTextEditor v-model="form.content" :placeholder="$t('encounter_notes.form.placeholder_content')" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.content }}</p>
        </div>
    </form>
</template>
```

- [ ] **Step 4: Create the modal**

Create `resources/js/Components/EncounterNoteModal.vue`:

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
import EncounterNoteForm from '@/Pages/EncounterNotes/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    patientId: {
        type: Number,
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
    appointments: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:open', 'saved'])

const is_edit = computed(() => Boolean(props.note?.id))

const action = computed(() =>
    is_edit.value
        ? route('patients.encounter-notes.update', [props.patientId, props.note.id])
        : route('patients.encounter-notes.store', props.patientId),
)

const method = computed(() => (is_edit.value ? 'put' : 'post'))

const title = computed(() =>
    is_edit.value
        ? trans('encounter_notes.modal.edit_title')
        : trans('encounter_notes.modal.new_title'),
)

const description = computed(() =>
    is_edit.value
        ? trans('encounter_notes.modal.edit_description')
        : trans('encounter_notes.modal.new_description'),
)

const submit_label = computed(() =>
    is_edit.value
        ? trans('encounter_notes.modal.submit_update')
        : trans('encounter_notes.modal.submit_create'),
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
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <EncounterNoteForm
                :key="note?.id ?? 'new'"
                :action="action"
                :method="method"
                :note="note"
                :types="types"
                :appointments="appointments"
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
                    form="encounter-note-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ submit_label }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 5: Create the tab**

Create `resources/js/Components/EncountersTab.vue`:

```vue
<script setup>
import EncounterNoteModal from '@/Components/EncounterNoteModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useEncounterNoteManager } from '@/composables/useEncounterNoteManager'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    notes: {
        type: Array,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    appointments: {
        type: Array,
        default: () => [],
    },
})

const {
    modal_open,
    editing_note,
    confirm_open,
    deleting_note,
    deleting,
    openCreate,
    openEdit,
    handleSaved,
    askDelete,
    confirmDelete,
    sign,
    coSign,
} = useEncounterNoteManager(props.patientId)

const statusClasses = {
    Unsigned: 'bg-light-yellow-100 text-light-yellow-700',
    Signed: 'bg-tropical-teal-100 text-tropical-teal-700',
    CoSigned: 'bg-cerulean-100 text-cerulean-700',
}

function snippet(html) {
    const text = (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    return text.length > 80 ? text.slice(0, 80) + '…' : text
}
</script>

<template>
    <div class="flex items-center justify-between px-6 py-4">
        <h2 class="font-bold text-foreground">{{ $t('encounter_notes.tab.heading') }}</h2>
        <button
            type="button"
            data-testid="new-encounter-note-button"
            @click="openCreate"
            class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
        >
            {{ $t('encounter_notes.tab.new') }}
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
        {{ $t('encounter_notes.tab.empty') }}
    </div>

    <table v-else class="w-full text-sm">
        <thead>
            <tr class="border-b border-border text-left">
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.title') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.type') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.encounter_date') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.status') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('encounter_notes.columns.actions') }}</th>
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
                    <div v-if="note.signer_name" class="mt-0.5 text-xs text-muted-foreground">
                        {{ $t('encounter_notes.signed_by', { name: note.signer_name }) }}
                        <template v-if="note.co_signer_name">
                            · {{ $t('encounter_notes.co_signed_by', { name: note.co_signer_name }) }}
                        </template>
                    </div>
                </td>
                <td class="px-6 py-3">
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ note.type_label }}
                    </span>
                </td>
                <td class="px-6 py-3 text-muted-foreground">{{ formatDate(note.encounter_date, DATE_SHORT) }}</td>
                <td class="px-6 py-3">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="statusClasses[note.status] ?? 'bg-muted text-muted-foreground'"
                    >
                        {{ note.status_label }}
                    </span>
                </td>
                <td class="px-6 py-3 text-right whitespace-nowrap">
                    <button
                        v-if="note.can_sign"
                        type="button"
                        data-testid="encounter-note-sign"
                        @click="sign(note)"
                        class="rounded-lg border border-tropical-teal-200 px-3 py-1.5 text-xs font-bold text-tropical-teal-700 hover:bg-tropical-teal-50"
                    >
                        {{ $t('encounter_notes.actions.sign') }}
                    </button>
                    <button
                        v-if="note.can_co_sign"
                        type="button"
                        @click="coSign(note)"
                        class="ml-2 rounded-lg border border-cerulean-200 px-3 py-1.5 text-xs font-bold text-cerulean-700 hover:bg-cerulean-50"
                    >
                        {{ $t('encounter_notes.actions.co_sign') }}
                    </button>
                    <button
                        v-if="note.can_edit"
                        type="button"
                        data-testid="encounter-note-edit"
                        @click="openEdit(note)"
                        class="ml-2 rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('encounter_notes.actions.edit') }}
                    </button>
                    <button
                        v-if="note.can_delete"
                        type="button"
                        @click="askDelete(note)"
                        class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('encounter_notes.actions.delete') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <EncounterNoteModal
        v-model:open="modal_open"
        :patient-id="patientId"
        :note="editing_note"
        :types="types"
        :appointments="appointments"
        @saved="handleSaved"
    />

    <ConfirmDialog
        v-model:open="confirm_open"
        :title="trans('encounter_notes.actions.delete')"
        :description="deleting_note ? trans('encounter_notes.delete_confirm') : ''"
        :confirm-label="trans('encounter_notes.actions.delete')"
        :processing="deleting"
        @confirm="confirmDelete"
    />
</template>
```

- [ ] **Step 6: Wire the tab into `Patients/Show.vue`**

Add the import alongside the other component imports:

```js
import EncountersTab from '@/Components/EncountersTab.vue'
```

Add the three new props inside `defineProps({ ... })` (after `note_types`):

```js
    encounter_notes: {
        type: Array,
        default: null,
    },
    encounter_note_types: {
        type: Array,
        default: () => [],
    },
    patient_appointments: {
        type: Array,
        default: () => [],
    },
```

Add `'encounters'` to the tab whitelist so a deep-linked `?tab=encounters` is honored:

```js
const active_tab = ref(['demographics', 'contacts', 'notes', 'encounters', 'discussions'].includes(initial_tab) ? initial_tab : 'demographics')
```

Add a tab button after the Notes button and before the Discussions button (inside the top tab group `<div class="flex bg-muted/40 p-1">`):

```html
                <button
                    type="button"
                    data-testid="patient-tab-encounters"
                    @click="active_tab = 'encounters'"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                    :class="active_tab === 'encounters'
                        ? 'bg-card text-foreground'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ $t('patients.show.tab_encounters') }}
                </button>
```

Render the tab panel after the `<NotesTab ... />` block (still inside the top card):

```html
            <EncountersTab
                v-if="active_tab === 'encounters'"
                :patient-id="patient.id"
                :notes="encounter_notes"
                :types="encounter_note_types"
                :appointments="patient_appointments"
            />
```

- [ ] **Step 7: Add the tab label to patient chart copy**

In `lang/en/patients.php`, inside the `'show' => [ ... ]` block, add next to the other `tab_*` keys:

```php
        'tab_encounters' => 'Encounters',
```

(If the tab labels live under a different key in that file, place `tab_encounters` beside the existing `tab_notes` key.)

- [ ] **Step 8: Build assets**

Run: `npm run build`
Expected: build succeeds with no errors referencing the new files.

- [ ] **Step 9: Commit**

```bash
git add resources/js/composables/useEncounterNoteManager.js "resources/js/Pages/EncounterNotes/Partials/Form.vue" resources/js/Components/EncounterNoteModal.vue resources/js/Components/EncountersTab.vue resources/js/Pages/Patients/Show.vue lang/en/encounter_notes.php lang/en/patients.php
git commit -m "feat: add encounter notes chart tab UI"
```

---

### Task 8: Browser test + full verification

**Files:**
- Create: `tests/Browser/EncounterNotesTest.php`

**Interfaces:**
- Consumes: `data-testid` hooks `patient-tab-encounters`, `new-encounter-note-button`, `encounter-note-sign`, `encounter-note-edit`.

- [ ] **Step 1: Write the browser test**

Create `tests/Browser/EncounterNotesTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
});

test('a doctor can create and sign an encounter note, which locks editing', function () {
    $user = User::factory()->withRole(UserRole::Doctor)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    $page->assertNoJavascriptErrors()
        ->click('[data-testid="patient-tab-encounters"]')
        ->click('[data-testid="new-encounter-note-button"]')
        ->assertSee('New Encounter Note')
        ->select('#encounter-note-form select', 'Progress')
        ->type('input[type="text"]', 'Initial visit note')
        ->click('button[type="submit"][form="encounter-note-form"]')
        ->assertNoJavascriptErrors();

    // The created note is Unsigned and can be signed by its author.
    $page->click('[data-testid="encounter-note-sign"]')
        ->assertSee('Signed')
        ->assertNoJavascriptErrors();

    expect($patient->encounterNotes()->count())->toBe(1)
        ->and($patient->encounterNotes()->first()->status->value)->toBe('Signed');
})->group('browser');
```

Note: the form requires content (RichTextEditor). If the submit fails validation on `content` in the browser, add a `->type('.ProseMirror', 'Seen today')` (or the editor's contenteditable selector — inspect `RichTextEditor.vue` for the correct selector) before clicking submit. Verify the editor selector during implementation.

- [ ] **Step 2: Run the browser test**

Run: `php artisan test --compact --filter=EncounterNotesTest`
Expected: PASS. If it fails on the content field, apply the selector fix noted above and re-run.

- [ ] **Step 3: Commit**

```bash
git add tests/Browser/EncounterNotesTest.php
git commit -m "test: browser coverage for encounter notes"
```

- [ ] **Step 4: Full verification**

Run: `php artisan test --compact --filter="EncounterNote|EncounterNotes|PatientChartTest|PatientRecordsTabsTest"`
Expected: all PASS.

Run: `npm run build`
Expected: succeeds.

- [ ] **Step 5: Confirm the seeder note**

The new `encounter_notes` permissions require `RoleAndPermissionSeeder` to have run in any environment (tests seed it explicitly; production/dev needs `php artisan db:seed --class=RoleAndPermissionSeeder`). Note this in the PR description.
