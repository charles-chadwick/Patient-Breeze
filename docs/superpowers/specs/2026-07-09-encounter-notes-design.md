# Patient Encounter Notes Design

**Date:** 2026-07-09

## Summary

Add **Encounter Notes** to patients: a clinical documentation record that is
**separate from the existing polymorphic `Note`**. Each encounter note has a type,
encounter date, title, and content, plus a signing workflow (Unsigned → Signed →
Co-signed). Notes are patient-scoped and may optionally link to an appointment.

This is a self-contained subsystem: data model, enums, model, authorization, backend
flow, a new patient-chart tab, and tests.

## Decisions (from brainstorming)

- **Association:** patient-scoped (`patient_id`), with an **optional** `appointment_id`.
- **Status:** a real **signing workflow**, not a free field. Status is driven by
  sign/co-sign actions, never edited directly on the form.
- **Types:** standard clinical set — Progress, Initial Visit, Follow-up, Consultation,
  Procedure, Discharge Summary, Telephone.
- **UI:** a new **Encounters** tab in the patient chart's top tab group.
- **Signing rules:** the author signs their own note; co-sign must be a **different**
  user and is only allowed after the note is signed.
- **Locking:** signing freezes title/content/type/encounter_date (read-only). Only
  unsigned notes are editable/deletable.
- **Role access (confirmed):** same grants as the regular `notes` resource — Staff,
  Nurse, and Medical Assistant get view/create/update; Doctor additionally gets delete;
  SuperAdmin gets all. Any author-capable user (different from the signer) may co-sign.

## 1. Data model — `encounter_notes`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `patient_id` | FK → patients | required, `cascadeOnDelete`, indexed |
| `appointment_id` | FK → appointments | nullable, `nullOnDelete` |
| `author_id` | FK → users | creator; `restrictOnDelete` |
| `type` | string | `EncounterNoteType` |
| `encounter_date` | date | required |
| `title` | string | required |
| `content` | longText | required (HTML, via `RichTextEditor`) |
| `status` | string | `EncounterNoteStatus`, default `Unsigned` |
| `signed_by` | nullable FK → users | `nullOnDelete` |
| `signed_at` | nullable timestamp | |
| `co_signed_by` | nullable FK → users | `nullOnDelete` |
| `co_signed_at` | nullable timestamp | |
| timestamps, softDeletes | | matches `Note` |

## 2. Enums

- **`EncounterNoteType`** (string-backed): `Progress`, `InitialVisit`, `FollowUp`,
  `Consultation`, `Procedure`, `DischargeSummary`, `Telephone`. Provides `label()`
  (`__('enums.encounter_note_type.'.$this->value)`) and `values()`.
- **`EncounterNoteStatus`** (string-backed): `Unsigned` (default), `Signed`, `CoSigned`.
  Same `label()`/`values()` shape (`enums.encounter_note_status.*`).

Both mirror `App\Enums\NoteType`.

## 3. Model & relations

`App\Models\EncounterNote`:

- Traits: `HasFactory`, `LogsActivity`, `Searchable` (title, content), `SoftDeletes`,
  `Sortable` (title, type, encounter_date, status).
- `$fillable`: `type`, `encounter_date`, `title`, `content`, `appointment_id`.
  (`author_id`, `status`, and all signing columns are set by actions, never mass-assigned.)
- Casts: `type => EncounterNoteType`, `status => EncounterNoteStatus`,
  `encounter_date => 'date'`, `signed_at`/`co_signed_at => 'datetime'`.
- Relations: `patient()`, `appointment()`, `author()` (users), `signer()`
  (`signed_by`), `coSigner()` (`co_signed_by`).
- Helper: `isEditable(): bool` → `status === EncounterNoteStatus::Unsigned`.
- `getActivitylogOptions()`: `LogOptions::defaults()->logOnlyDirty()->logFillable()`.

`App\Models\Patient` gains `encounterNotes(): HasMany`.

## 4. Authorization

Add `'encounter_notes'` to `UserRole::RESOURCES`. This auto-generates
`view_encounter_notes`, `create_encounter_notes`, `update_encounter_notes`,
`delete_encounter_notes` via `UserRole::allPermissions()`. Grant them in
`UserRole::grants()` to match the `notes` resource:

- SuperAdmin: all four (via `array_fill_keys(self::RESOURCES, self::ACTIONS)` — automatic).
- Doctor: `['view','create','update','delete']`.
- Nurse, MedicalAssistant, Staff: `['view','create','update']`.

Re-run `RoleAndPermissionSeeder` (it uses `findOrCreate` + `syncPermissions`, so it is
idempotent).

`App\Policies\EncounterNotePolicy`:

- `viewAny` / `view`: `can('view_encounter_notes')`.
- `create`: `can('create_encounter_notes')`.
- `update`: `can('update_encounter_notes')` **and** `$note->isEditable()`.
- `delete`: `can('delete_encounter_notes')` **and** `$note->isEditable()`.
- `sign`: `can('update_encounter_notes')`, `status === Unsigned`, **and**
  `$user->id === $note->author_id`.
- `coSign`: `can('update_encounter_notes')`, `status === Signed`, **and**
  `$user->id !== $note->signed_by`.

Sign/co-sign stay as policy logic — no new permission strings, keeping the 4-action grid
intact. Register the policy (auto-discovery via `EncounterNote` → `EncounterNotePolicy`).

## 5. Backend flow

**Actions** (`app/Actions/`):

- `CreateEncounterNoteAction::execute(Patient $patient, User $author, array $validated): EncounterNote`
  — creates the note under the patient with `author_id = $author->id`, status defaulting
  to `Unsigned`, and `appointment_id` from the validated data (nullable).
- `SignEncounterNoteAction::execute(EncounterNote $note, User $user): void`
  — sets `status = Signed`, `signed_by = $user->id`, `signed_at = now()`.
- `CoSignEncounterNoteAction::execute(EncounterNote $note, User $user): void`
  — sets `status = CoSigned`, `co_signed_by = $user->id`, `co_signed_at = now()`.

**Requests** (`app/Http/Requests/`):

- `StoreEncounterNoteRequest`:
  - `type` → required, `Rule::enum(EncounterNoteType::class)`
  - `encounter_date` → required, date
  - `title` → required, string, max:255
  - `content` → required, string
  - `appointment_id` → nullable, integer, must exist and belong to the route patient
    (`Rule::exists('appointments','id')->where('patient_id', $patient->id)`).
- `UpdateEncounterNoteRequest`: same minus `appointment_id` reassignment concerns —
  allow `type`, `encounter_date`, `title`, `content`, and nullable `appointment_id`
  (validated against the note's patient).

**Controller** `App\Http\Controllers\EncounterNoteController` — patient-scoped routes in
`routes/web.php`:

```
POST   patients/{patient}/encounter-notes                       encounter-notes.store
PUT    patients/{patient}/encounter-notes/{encounterNote}       encounter-notes.update
DELETE patients/{patient}/encounter-notes/{encounterNote}       encounter-notes.destroy
POST   patients/{patient}/encounter-notes/{encounterNote}/sign  encounter-notes.sign
POST   patients/{patient}/encounter-notes/{encounterNote}/co-sign encounter-notes.co-sign
```

Each method authorizes via the policy, calls the relevant action, and
`redirect()->back()->with('success', __('flash.encounter_notes.<event>'))`. Use scoped
route-model binding so `{encounterNote}` is resolved within `{patient}`.

## 6. Frontend (patient chart)

`PatientController@show` additions:

- `encounter_notes` (deferred, like `notes`): each item shaped for the UI —
  `id`, `type`, `type_label`, `encounter_date`, `title`, `content`, `status`,
  `status_label`, `author_name`, `signer_name`, `co_signer_name`, `signed_at`,
  `co_signed_at`, `appointment_id`, and precomputed flags `can_edit`, `can_sign`,
  `can_co_sign` (from the policy for the current user).
- `encounter_note_types` → `EncounterNoteType::values()`.
- `patient_appointments` → lightweight list (`id`, `date`, `reason`) for the form's
  optional appointment select.

`Patients/Show.vue`: add an **Encounters** tab button (with a `data-testid`) to the top
tab group, rendering `<EncountersTab>`.

New Vue components (mirroring the Notes pattern):

- `resources/js/Components/EncountersTab.vue` — table: title + content snippet, type badge,
  encounter date, status badge, and conditional action buttons (Edit/Delete when
  `can_edit`, Sign when `can_sign`, Co-sign when `can_co_sign`). Skeleton loading state
  for the deferred prop, matching `NotesTab`.
- `resources/js/Components/EncounterNoteModal.vue` — `ui/dialog` wrapper + external submit
  button in `DialogFooter` (like `NoteModal`/`MedicationModal`).
- `resources/js/Pages/EncounterNotes/Partials/Form.vue` — `type` select, `DatePicker`
  encounter date, `title` input, `RichTextEditor` content, optional appointment select;
  `formId` + `success` emit convention.
- `resources/js/composables/useEncounterNoteManager.js` — mirrors `useNoteManager`
  (modal open state, editing target, create/edit/save handlers, delete confirm).

Sign/Co-sign are `router.post` calls to the sign/co-sign routes with `preserveScroll`,
reloading the deferred `encounter_notes` prop on success.

## 7. i18n

- `lang/en/enums.php`: `encounter_note_type.*` (7 keys) and `encounter_note_status.*`
  (3 keys).
- `lang/en/encounter_notes.php`: tab heading/empty, form labels/placeholders, column
  headers, action labels (edit/delete/sign/co_sign), signed-by/co-signed-by copy.
- `lang/en/flash.php`: `encounter_notes.created/updated/deleted/signed/co_signed`.

## 8. Testing

- **Feature** `tests/Feature/EncounterNoteControllerTest.php`:
  - store creates a note with `author_id` = current user and status `Unsigned`.
  - update succeeds when `Unsigned`; is 403 when `Signed` (locked).
  - sign by the author transitions `Unsigned → Signed` and records `signed_by`/`signed_at`.
  - sign by a non-author is forbidden.
  - co-sign by a different user transitions `Signed → CoSigned`; co-sign by the signer is
    forbidden; co-sign of an `Unsigned` note is forbidden.
  - delete succeeds when `Unsigned`, forbidden when `Signed`.
  - `Patients/Show` exposes `encounter_notes`, `encounter_note_types`,
    `patient_appointments`.
- **Factory** `EncounterNoteFactory` with `unsigned` (default), `signed`, `coSigned`
  states.
- **Browser** `tests/Browser/EncounterNotesTest.php`: open the Encounters tab, create a
  note via the modal, sign it, confirm the Edit action disappears once signed, and (acting
  as a second user) co-sign it.

## Out of Scope

- No amendments/addenda to signed notes, no un-signing, no PDF export.
- Content editing after signing is not supported — signed notes are locked.
- Role-gated co-signing (any different staff user with update permission may co-sign).
