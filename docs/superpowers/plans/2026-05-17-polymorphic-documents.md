# Polymorphic Documents Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a polymorphic `documents` table and `Document` model that attaches to Patient, Appointment, User, and Contact, with each record backed by a single Spatie Media Library file.

**Architecture:** A `Document` model owns its file via Spatie's `HasMedia` (single-file `file` collection), and morphs to its parent via `documentable`. Existing models get a `documents(): MorphMany` relation. This keeps document media separate from avatar media already on Patient and User.

**Tech Stack:** Laravel 13, Spatie Media Library v11, Pest 4, PHP 8.4 enums

---

## File Map

| Action | Path |
|---|---|
| Create | `app/Enums/DocumentType.php` |
| Create | `database/migrations/2026_05_17_000000_create_documents_table.php` (actual name from artisan) |
| Create | `app/Models/Document.php` |
| Create | `database/factories/DocumentFactory.php` |
| Create | `tests/Feature/DocumentTest.php` |
| Modify | `app/Models/Patient.php` — add `documents()` |
| Modify | `app/Models/Appointment.php` — add `documents()` |
| Modify | `app/Models/User.php` — add `documents()` |
| Modify | `app/Models/Contact.php` — add `documents()` |

---

## Task 1: DocumentType Enum

**Files:**
- Create: `app/Enums/DocumentType.php`

- [ ] **Step 1: Create the enum file**

```php
<?php

namespace App\Enums;

enum DocumentType: string
{
    case LabResult = 'LabResult';
    case Insurance = 'Insurance';
    case Referral = 'Referral';
    case Consent = 'Consent';
    case Prescription = 'Prescription';
    case Identification = 'Identification';
    case Certification = 'Certification';
    case Note = 'Note';
    case Other = 'Other';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 2: Verify it loads**

```bash
php artisan tinker --execute 'echo App\Enums\DocumentType::LabResult->value;'
```

Expected output: `LabResult`

- [ ] **Step 3: Commit**

```bash
git add app/Enums/DocumentType.php
git commit -m "feat: add DocumentType enum"
```

---

## Task 2: Migration

**Files:**
- Create: migration file (name provided by artisan)

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration create_documents_table --no-interaction
```

Note the generated filename (e.g. `2026_05_17_XXXXXX_create_documents_table.php`).

- [ ] **Step 2: Fill in the migration**

Open the generated file and replace its `up()` and `down()` methods:

```php
public function up(): void
{
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->morphs('documentable');
        $table->string('type');
        $table->string('name');
        $table->date('document_date')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::dropIfExists('documents');
}
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate --no-interaction
```

Expected: Migration runs with no errors and `documents` table appears in the database.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add documents migration"
```

---

## Task 3: Document Model

**Files:**
- Create: `app/Models/Document.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DocumentTest.php`:

```php
<?php

use App\Enums\DocumentType;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Patient;
use App\Models\User;

it('creates a document belonging to a patient', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::LabResult,
        'name' => 'CBC Panel',
        'document_date' => '2026-01-15',
        'notes' => 'Routine bloodwork',
    ]);

    expect($document->documentable)->toBeInstanceOf(Patient::class)
        ->and($document->documentable->id)->toBe($patient->id)
        ->and($document->name)->toBe('CBC Panel')
        ->and($document->type)->toBe(DocumentType::LabResult)
        ->and($document->document_date->format('Y-m-d'))->toBe('2026-01-15')
        ->and($document->notes)->toBe('Routine bloodwork');
});

it('creates a document belonging to an appointment', function (): void {
    $appointment = Appointment::factory()->create();
    $document = $appointment->documents()->create([
        'type' => DocumentType::Referral,
        'name' => 'Specialist Referral',
    ]);

    expect($document->documentable)->toBeInstanceOf(Appointment::class)
        ->and($document->documentable->id)->toBe($appointment->id);
});

it('creates a document belonging to a user', function (): void {
    $user = User::factory()->create();
    $document = $user->documents()->create([
        'type' => DocumentType::Certification,
        'name' => 'BLS Certification',
    ]);

    expect($document->documentable)->toBeInstanceOf(User::class)
        ->and($document->documentable->id)->toBe($user->id);
});

it('creates a document belonging to a contact', function (): void {
    $patient = Patient::factory()->create();
    $contact = Contact::factory()->for($patient, 'contactable')->create();
    $document = $contact->documents()->create([
        'type' => DocumentType::Consent,
        'name' => 'ROI Form',
    ]);

    expect($document->documentable)->toBeInstanceOf(Contact::class)
        ->and($document->documentable->id)->toBe($contact->id);
});

it('allows document_date and notes to be null', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Note,
        'name' => 'Intake Note',
    ]);

    expect($document->document_date)->toBeNull()
        ->and($document->notes)->toBeNull();
});

it('casts type to DocumentType enum', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Insurance,
        'name' => 'Insurance Card',
    ]);

    expect($document->fresh()->type)->toBe(DocumentType::Insurance);
});

it('soft deletes a document', function (): void {
    $patient = Patient::factory()->create();
    $document = $patient->documents()->create([
        'type' => DocumentType::Other,
        'name' => 'Miscellaneous',
    ]);

    $document->delete();

    expect(Document::find($document->id))->toBeNull()
        ->and(Document::withTrashed()->find($document->id))->not->toBeNull();
});

it('isolates documents by documentable model', function (): void {
    $patient = Patient::factory()->create();
    $user = User::factory()->create();

    Document::factory()->count(2)->for($patient, 'documentable')->create();
    Document::factory()->count(3)->for($user, 'documentable')->create();

    expect($patient->documents()->count())->toBe(2)
        ->and($user->documents()->count())->toBe(3);
});

it('document factory produces valid documents', function (): void {
    $patient = Patient::factory()->create();
    $document = Document::factory()->for($patient, 'documentable')->create();

    expect($document->type)->toBeInstanceOf(DocumentType::class)
        ->and($document->name)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter=DocumentTest
```

Expected: Errors about missing `Document` class and missing `documents()` relations.

- [ ] **Step 3: Create the Document model**

```bash
php artisan make:model Document --no-interaction
```

Replace the generated file at `app/Models/Document.php` with:

```php
<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'document_date',
        'notes',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'document_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly([
            'type', 'name', 'document_date', 'notes',
        ]);
    }
}
```

- [ ] **Step 4: Create the DocumentFactory**

```bash
php artisan make:factory DocumentFactory --no-interaction
```

Replace the generated file at `database/factories/DocumentFactory.php` with:

```php
<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(DocumentType::cases()),
            'name' => fake()->words(3, true),
            'document_date' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 5: Add `documents()` MorphMany to Patient**

In `app/Models/Patient.php`, `MorphMany` is already imported and `Document` is in the same namespace — no imports needed. Add the method after `discussions()`:

```php
public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'documentable');
}
```

- [ ] **Step 6: Add `documents()` MorphMany to Appointment**

In `app/Models/Appointment.php`, add the `MorphMany` import (`Document` is in the same namespace):

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;
```

Then add the method after `attachProvider()`:

```php
public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'documentable');
}
```

- [ ] **Step 7: Add `documents()` MorphMany to User**

In `app/Models/User.php`, `MorphMany` is already imported and `Document` is in the same namespace — no imports needed. Add the method after `contacts()`:

```php
public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'documentable');
}
```

- [ ] **Step 8: Add `documents()` MorphMany to Contact**

In `app/Models/Contact.php`, add the `MorphMany` import (`Document` is in the same namespace):

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;
```

Then add the method after `contactable()`:

```php
public function documents(): MorphMany
{
    return $this->morphMany(Document::class, 'documentable');
}
```

- [ ] **Step 9: Run tests to verify they pass**

```bash
php artisan test --compact --filter=DocumentTest
```

Expected: All 9 tests pass.

- [ ] **Step 10: Run Pint formatter**

```bash
vendor/bin/pint --dirty --format agent
```

Expected: Pint reports no errors or fixes any style issues automatically.

- [ ] **Step 11: Commit**

```bash
git add app/Models/Document.php database/factories/DocumentFactory.php \
        app/Models/Patient.php app/Models/Appointment.php \
        app/Models/User.php app/Models/Contact.php \
        tests/Feature/DocumentTest.php
git commit -m "feat: add polymorphic Document model with Spatie media collection"
```

---
