# Medication Catalog CRUD — Design

**Date:** 2026-07-10
**Status:** Approved for planning

## Summary

Add full CRUD for the shared **medication catalog** (the `Medication` model / formulary).
Today staff can only *search* the catalog from a picker when adding a medication to a
patient (`MedicationController@search`); there is no way to view, create, edit, or delete
the catalog entries themselves. This adds admin-section pages to manage that catalog,
mirroring the existing `Users` admin resource pattern end to end.

Scope note: this governs the **catalog** (`Medication`), not a patient's assigned
medications (`PatientMedication`, already CRUDable on the patient chart). Because
`PatientMedication` stores its own denormalized copy of the med fields and holds **no
foreign key** to `Medication`, deleting a catalog entry never affects meds already
assigned to patients. `Medication` already uses `SoftDeletes`, so deletes are soft.

## Operations

Index, Create, Edit, Delete. No standalone read-only Show page (the Edit page serves that
role). Delete is a soft delete triggered from an Index row.

## Authorization

Enum-driven permissions (Spatie). Add `'medications'` to `UserRole::RESOURCES`, which
auto-generates `view_medications`, `create_medications`, `update_medications`,
`delete_medications`.

**Grants:** *all* roles receive full CRUD on the catalog (Super Admin already gets every
resource via `array_fill_keys`; add `'medications' => ['view','create','update','delete']`
to Doctor, Nurse, Medical Assistant, and Staff in `UserRole::grants()`). The med-picker
`search` endpoint remains unauthenticated-by-permission as it is today — this design does
not change picker access.

`RoleAndPermissionSeeder` must be re-run after the enum change (it reads
`UserRole::allPermissions()` / `permissions()`).

## Backend

### Model — `app/Models/Medication.php`
- Add concerns: `use Searchable, Sortable, Filterable, HasListing;`
- `searchableFields(): ['name', 'type', 'ndc']`
- `sortableFields(): ['name' => 'name', 'type' => 'type', 'dose_form' => 'dose_form', 'ndc' => 'ndc']`
- `filterableFields(): ['dose_form' => 'dose_form']`
- `scopeListing(Builder $query, Request $request): array` → `$this->paginatedListing($query, $request, 'medications', 'name')`
- Keep existing `matchingSearch` and `searchCatalog` scopes unchanged.

### Policy — `app/Policies/MedicationPolicy.php`
Mirror `UserPolicy`: `viewAny`/`view`/`create`/`update`/`delete`/`restore` gated on the
corresponding `*_medications` permission (`forceDelete` → Super Admin only, matching the
Users convention). Laravel model→policy auto-discovery resolves it; no manual registration
needed unless the app registers policies explicitly (follow existing convention).

### Requests
- `StoreMedicationRequest`, `UpdateMedicationRequest` (`authorize()` returns `true`; the
  controller enforces the policy, matching `StoreUserRequest`).
- Rules:
  - `type` → `['required', 'string', 'max:255']`
  - `name` → `['required', 'string', 'max:255']`
  - `dosage` → `['required', 'string', 'max:255']`
  - `dose_form` → `['required', Rule::in(DoseForm::values())]`
  - `ndc` → `['required', 'string', 'max:255', Rule::unique('medications', 'ndc')]`
    (the `medications.ndc` column is non-nullable; NDC is a unique identifier). On update,
    add `->ignore($medication->id)` (respecting soft deletes as Laravel's unique rule
    does by default — it counts soft-deleted rows; acceptable since deletes are rare, but
    note it).

### Controller — `app/Http/Controllers/MedicationController.php`
Add to the existing controller (keep `search`):
- `index(Request)` → `$this->authorize('viewAny', Medication::class)`; render
  `Medications/Index` with `...Medication::listing($request)` and
  `'dose_form_options' => DoseForm::values()`.
- `create()` → authorize `create`; render `Medications/Form` with `dose_form_options`.
- `store(StoreMedicationRequest)` → authorize `create`; `Medication::create($request->validated())`;
  redirect `medications.index` with `flash.medications.created`.
- `edit(Medication)` → authorize `update`; render `Medications/Form` with `medication` + `dose_form_options`.
- `update(UpdateMedicationRequest, Medication)` → authorize `update`; `$medication->update(...)`;
  redirect with `flash.medications.updated`.
- `destroy(Medication)` → authorize `delete`; `$medication->delete()`; redirect with `flash.medications.deleted`.

### Routes — `routes/web.php`
Inside the existing `admin` prefix group:
```php
Route::resource('medications', MedicationController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
```
URLs `/admin/medications…`; route names `medications.index/create/store/edit/update/destroy`.
No name collision with the existing `medications.search`.

## Frontend (mirrors `resources/js/Pages/Users`)

- `Pages/Medications/Index.vue` — `DashboardLayout`, breadcrumbs `[nav.medications]`.
  Header with count badge + "New" link; `SearchInput`, `SortDropdown` (name/type/form/ndc),
  `FilterDropdown` (dose form), table columns **Name, Type, Dosage, Form, NDC**, per-row
  **Edit** link and **Delete** button (confirm dialog → `router.delete(route('medications.destroy', id))`),
  empty state, and the shared pagination footer. Responsive column hiding as in `Users/Index`.
- `Pages/Medications/Form.vue` — create/edit wrapper: computes `isEditing`, breadcrumbs,
  `formAction` (`medications.store` vs `medications.update`), `formMethod` (`post`/`put`),
  `backHref` (`medications.index`). Renders the partial.
- `Pages/Medications/Partials/Form.vue` — `useForm` with `type`, `name`, `dosage`,
  `dose_form`, `ndc`; text inputs plus a `<select>` for `dose_form` from `dose_form_options`;
  submit + cancel; field-level validation error display following the Users partial.
- `Layouts/DashboardLayout.vue` — add
  `{ label: trans('nav.medications'), route: 'medications.index', icon: Pill }` to the
  Administration section `children` (import `Pill` from `lucide-vue-next`).

## i18n + flash

- `lang/en/nav.php` — add `'medications' => 'Medications'`.
- `lang/en/medications.php` — add a **`catalog`** namespace so admin strings do not collide
  with the existing patient-picker strings (`heading`, `search`, `form` already used):
  `catalog.index.*` (heading, new button, search placeholder, column labels, empty,
  record_label, sort labels) and `catalog.form.*` (new/edit titles, field labels, submit,
  cancel).
- `lang/en/flash.php` — add under `medications`: `'created'`, `'updated'`, `'deleted'`.

## Testing

`tests/Feature/MedicationManagementTest.php` (Pest, factory-driven):
- Index page renders for a permitted user (`assertInertia` component `Medications/Index`).
- Store creates a catalog entry and redirects with the flash.
- Update edits an entry.
- Destroy soft-deletes (`assertSoftDeleted`).
- Validation: missing required fields and invalid `dose_form` fail; duplicate `ndc` fails.
- Authorization: a user whose role lacks `*_medications` (construct one, or revoke) gets 403.

Also run the **full suite** after changing `UserRole::RESOURCES`, since any existing test
asserting the generated-permission set or per-role permission counts will shift and must be
updated to include the four `*_medications` permissions.

## Out of scope

- No changes to the med-picker (`search`) endpoint or `PatientMedication`.
- No `type` enum (kept as free-text string, matching current data and search behavior).
- No bulk import / CSV, no NDC external validation.
