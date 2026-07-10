# Medication Catalog CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add full admin CRUD (index, create, edit, soft-delete) for the shared `Medication` catalog/formulary, mirroring the existing `Users` admin resource.

**Architecture:** Enum-driven Spatie permissions gate the new `medications` resource for all staff roles. The `Medication` model gains the shared listing concerns for search/sort/filter/pagination. A resource controller under the `admin` route prefix renders Inertia pages (`Medications/Index`, `Medications/Form`) that reuse the same components as the Users pages.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Tailwind v4, Spatie Permission, Pest 4.

## Global Constraints

- Naming (per user CLAUDE.md): variables `snake_case`, methods/functions `camelCase`, classes `TitleCase`. Prefer OOP.
- PHP 8: constructor property promotion, curly braces on all control structures, explicit return types + param type hints, PHPDoc array shapes.
- Follow existing sibling-file conventions exactly; reuse existing components (`SearchInput`, `SortDropdown`, `FilterDropdown`, shared pagination markup).
- After editing PHP, run `vendor/bin/pint --dirty --format agent` before committing.
- Tests are mandatory per change; run with `php artisan test --compact --filter=...`.
- The med-picker `search` endpoint and `PatientMedication` are OUT OF SCOPE — do not modify them.
- `Medication` uses `SoftDeletes`; deletes are soft. `PatientMedication` has no FK to `Medication`, so deletes never cascade.

---

### Task 1: Permissions & Policy

Add the `medications` resource to the permission enum (granting all staff roles full CRUD) and a policy mirroring `UserPolicy`.

**Files:**
- Modify: `app/Enums/UserRole.php` (`RESOURCES` const ~line 18; `grants()` ~lines 76-108)
- Create: `app/Policies/MedicationPolicy.php`
- Test: `tests/Feature/MedicationPolicyTest.php`

**Interfaces:**
- Produces: permissions `view_medications`, `create_medications`, `update_medications`, `delete_medications`; `MedicationPolicy` with `viewAny/view/create/update/delete/restore/forceDelete`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MedicationPolicyTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

it('generates the four medication permissions', function (): void {
    expect(UserRole::allPermissions())
        ->toContain('view_medications', 'create_medications', 'update_medications', 'delete_medications');
});

it('grants every staff role full medication catalog access', function (): void {
    foreach ([UserRole::SuperAdmin, UserRole::Doctor, UserRole::Nurse, UserRole::MedicalAssistant, UserRole::Staff] as $role) {
        expect($role->permissions())
            ->toContain('view_medications', 'create_medications', 'update_medications', 'delete_medications');
    }
});

it('lets a permitted user manage the catalog and forbids a role-less user', function (): void {
    $doctor = User::factory()->withRole(UserRole::Doctor)->create();
    $stranger = User::factory()->create();

    expect($doctor->can('create_medications'))->toBeTrue()
        ->and($stranger->can('create_medications'))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MedicationPolicyTest`
Expected: FAIL — medication permissions not present.

- [ ] **Step 3: Add `medications` to the resource list**

In `app/Enums/UserRole.php`, add `'medications'` to the end of the `RESOURCES` const:

```php
private const RESOURCES = ['patients', 'appointments', 'discussions', 'documents', 'contacts', 'notes', 'encounter_notes', 'users', 'medications'];
```

- [ ] **Step 4: Grant the resource to every non-SuperAdmin role**

In `grants()`, add `'medications' => ['view', 'create', 'update', 'delete'],` to the `Doctor`, the `Nurse, MedicalAssistant`, and the `Staff` arms. (SuperAdmin already receives every resource via `array_fill_keys(self::RESOURCES, self::ACTIONS)` — no change needed there.) Example for the Doctor arm:

```php
self::Doctor => [
    'patients' => ['view', 'create', 'update'],
    'appointments' => ['view', 'create', 'update', 'delete'],
    'discussions' => ['view', 'create', 'update', 'delete'],
    'documents' => ['view', 'create', 'update', 'delete'],
    'contacts' => ['view', 'create', 'update', 'delete'],
    'notes' => ['view', 'create', 'update', 'delete'],
    'encounter_notes' => ['view', 'create', 'update', 'delete'],
    'medications' => ['view', 'create', 'update', 'delete'],
],
```

Add the same `'medications' => ['view', 'create', 'update', 'delete'],` line to the `Nurse, MedicalAssistant` arm and the `Staff` arm.

- [ ] **Step 5: Create the policy**

Create `app/Policies/MedicationPolicy.php` (mirrors `UserPolicy`):

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_medications');
    }

    public function view(User $user, Medication $medication): bool
    {
        return $user->can('view_medications');
    }

    public function create(User $user): bool
    {
        return $user->can('create_medications');
    }

    public function update(User $user, Medication $medication): bool
    {
        return $user->can('update_medications');
    }

    public function delete(User $user, Medication $medication): bool
    {
        return $user->can('delete_medications');
    }

    public function restore(User $user, Medication $medication): bool
    {
        return $user->can('delete_medications');
    }

    public function forceDelete(User $user, Medication $medication): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value);
    }
}
```

(Laravel resolves `Medication` → `MedicationPolicy` by naming convention, matching how the other policies in `app/Policies` are auto-discovered. No manual registration.)

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=MedicationPolicyTest`
Expected: PASS (3 passing).

Also confirm the existing seeding test still passes (it derives counts from the enum, so it should):
Run: `php artisan test --compact --filter=DataSeedingTest`
Expected: PASS.

- [ ] **Step 7: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/UserRole.php app/Policies/MedicationPolicy.php tests/Feature/MedicationPolicyTest.php
git commit -m "feat: add medication catalog permissions and policy

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Model listing support

Give `Medication` the shared search/sort/filter/pagination concerns so the index page can use `Medication::listing($request)`.

**Files:**
- Modify: `app/Models/Medication.php`
- Test: `tests/Feature/MedicationCatalogModelTest.php`

**Interfaces:**
- Consumes: `HasListing::paginatedListing(Builder, Request, string $key, string $default_sort)` from `app/Models/Concerns/HasListing.php`.
- Produces: `Medication::listing(Request): array` returning `['medications' => LengthAwarePaginator, 'search' => string, 'sort_by' => string, 'direction' => string, 'filters' => array]`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MedicationCatalogModelTest.php`:

```php
<?php

use App\Models\Medication;
use Illuminate\Http\Request;

it('paginates the catalog keyed by medications sorted by name', function (): void {
    Medication::factory()->create(['name' => 'Zoloft']);
    Medication::factory()->create(['name' => 'Amoxicillin']);

    $result = Medication::listing(new Request());

    expect($result)->toHaveKeys(['medications', 'search', 'sort_by', 'direction', 'filters'])
        ->and($result['medications']->total())->toBe(2)
        ->and($result['medications']->first()->name)->toBe('Amoxicillin');
});

it('filters the catalog by a name search term', function (): void {
    Medication::factory()->create(['name' => 'Lisinopril']);
    Medication::factory()->create(['name' => 'Ibuprofen']);

    $result = Medication::listing(new Request(['search' => 'Lisin']));

    expect($result['medications']->total())->toBe(1)
        ->and($result['medications']->first()->name)->toBe('Lisinopril');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MedicationCatalogModelTest`
Expected: FAIL — `Call to undefined method ...::listing()`.

- [ ] **Step 3: Add concerns and listing scope**

In `app/Models/Medication.php`, add the concern imports and update the `use` trait line and class body. Add these `use` statements near the existing imports:

```php
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Illuminate\Http\Request;
```

Change the trait line from `use HasFactory, SoftDeletes;` to:

```php
use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;
```

Add these methods to the class (after the existing `searchCatalog` scope):

```php
public function scopeListing(Builder $query, Request $request): array
{
    return $this->paginatedListing($query, $request, 'medications', 'name');
}

/** @return list<string> */
protected function searchableFields(): array
{
    return ['name', 'type', 'ndc'];
}

/** @return array<string, string> */
protected function sortableFields(): array
{
    return [
        'name' => 'name',
        'type' => 'type',
        'dose_form' => 'dose_form',
        'ndc' => 'ndc',
    ];
}

/** @return array<string, string> */
protected function filterableFields(): array
{
    return ['dose_form' => 'dose_form'];
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=MedicationCatalogModelTest`
Expected: PASS (2 passing).

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Medication.php tests/Feature/MedicationCatalogModelTest.php
git commit -m "feat: add listing scope and search concerns to Medication

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Requests, Controller actions, Routes, flash & i18n

Wire the backend: form requests, the six controller actions, the resource route, and the flash/i18n strings the controller and pages need. This task's tests exercise the full HTTP surface.

**Files:**
- Create: `app/Http/Requests/StoreMedicationRequest.php`
- Create: `app/Http/Requests/UpdateMedicationRequest.php`
- Modify: `app/Http/Controllers/MedicationController.php`
- Modify: `routes/web.php` (inside the `admin` prefix group, ~lines 64-67)
- Modify: `lang/en/flash.php` (`medications` array ~lines 70-73)
- Modify: `lang/en/nav.php` (add `medications` label)
- Modify: `lang/en/medications.php` (add a `catalog` namespace)
- Test: `tests/Feature/MedicationManagementTest.php`

**Interfaces:**
- Consumes: `Medication::listing()` (Task 2); `MedicationPolicy` (Task 1); `DoseForm::values()`.
- Produces: routes `medications.index/create/store/edit/update/destroy`; Inertia components `Medications/Index` (props: `medications, search, sort_by, direction, filters, dose_form_options`) and `Medications/Form` (props: `medication?, dose_form_options`).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MedicationManagementTest.php`:

```php
<?php

use App\Enums\DoseForm;
use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }

    $this->actingAs(User::factory()->withRole(UserRole::Doctor)->create());
});

function validMedicationPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'Antibiotic',
        'name' => 'Amoxicillin',
        'dosage' => '500mg',
        'dose_form' => DoseForm::Capsule->value,
        'ndc' => '0093-4155-56',
    ], $overrides);
}

it('renders the catalog index', function (): void {
    Medication::factory()->create();

    $this->get(route('medications.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Index')
            ->has('medications.data')
            ->has('dose_form_options')
        );
});

it('renders the create form', function (): void {
    $this->get(route('medications.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Form')
            ->has('dose_form_options')
        );
});

it('stores a new medication', function (): void {
    $this->post(route('medications.store'), validMedicationPayload())
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('medications', ['name' => 'Amoxicillin', 'ndc' => '0093-4155-56']);
});

it('renders the edit form', function (): void {
    $medication = Medication::factory()->create();

    $this->get(route('medications.edit', $medication))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Medications/Form')
            ->where('medication.id', $medication->id)
            ->has('dose_form_options')
        );
});

it('updates a medication', function (): void {
    $medication = Medication::factory()->create();

    $this->put(route('medications.update', $medication), validMedicationPayload(['name' => 'Amoxil']))
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    expect($medication->fresh()->name)->toBe('Amoxil');
});

it('soft-deletes a medication', function (): void {
    $medication = Medication::factory()->create();

    $this->delete(route('medications.destroy', $medication))
        ->assertRedirect(route('medications.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted($medication);
});

it('validates required fields and dose form', function (): void {
    $this->post(route('medications.store'), validMedicationPayload(['name' => '', 'dose_form' => 'NotAForm']))
        ->assertSessionHasErrors(['name', 'dose_form']);
});

it('rejects a duplicate ndc on create but allows keeping it on update', function (): void {
    Medication::factory()->create(['ndc' => '0093-4155-56']);

    $this->post(route('medications.store'), validMedicationPayload())
        ->assertSessionHasErrors('ndc');

    $medication = Medication::factory()->create(['ndc' => '1111-2222-33']);
    $this->put(route('medications.update', $medication), validMedicationPayload(['ndc' => '1111-2222-33']))
        ->assertSessionHasNoErrors();
});

it('forbids a user without medication permissions', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('medications.index'))->assertForbidden();
    $this->post(route('medications.store'), validMedicationPayload())->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MedicationManagementTest`
Expected: FAIL — route `medications.index` not defined.

- [ ] **Step 3: Create the store request**

Create `app/Http/Requests/StoreMedicationRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\DoseForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'dose_form' => ['required', Rule::in(DoseForm::values())],
            'ndc' => ['required', 'string', 'max:255', Rule::unique('medications', 'ndc')],
        ];
    }
}
```

- [ ] **Step 4: Create the update request**

Create `app/Http/Requests/UpdateMedicationRequest.php` (identical except the `ndc` unique rule ignores the current record via the route-bound model):

```php
<?php

namespace App\Http\Requests;

use App\Enums\DoseForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'dose_form' => ['required', Rule::in(DoseForm::values())],
            'ndc' => ['required', 'string', 'max:255', Rule::unique('medications', 'ndc')->ignore($this->route('medication'))],
        ];
    }
}
```

- [ ] **Step 5: Add controller actions**

Replace `app/Http/Controllers/MedicationController.php` with (keeps the existing `search` method):

```php
<?php

namespace App\Http\Controllers;

use App\Enums\DoseForm;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Medication::class);

        return Inertia::render('Medications/Index', [
            ...Medication::listing($request),
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    /**
     * Search the medication catalog for the "add medication" modal.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        return response()->json(['medications' => Medication::searchCatalog($search)]);
    }

    public function create(): Response
    {
        $this->authorize('create', Medication::class);

        return Inertia::render('Medications/Form', [
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        $this->authorize('create', Medication::class);

        Medication::create($request->validated());

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.created'));
    }

    public function edit(Medication $medication): Response
    {
        $this->authorize('update', $medication);

        return Inertia::render('Medications/Form', [
            'medication' => $medication,
            'dose_form_options' => DoseForm::values(),
        ]);
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $this->authorize('update', $medication);

        $medication->update($request->validated());

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.updated'));
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        $this->authorize('delete', $medication);

        $medication->delete();

        return redirect()->route('medications.index')
            ->with('success', __('flash.medications.deleted'));
    }
}
```

- [ ] **Step 6: Register the resource route**

In `routes/web.php`, inside the existing `Route::prefix('admin')->group(function () { ... })` block (where the `users` resource is registered), add:

```php
Route::resource('medications', MedicationController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
```

(`MedicationController` is already imported/used by the existing `medications.search` route, so no new import is needed. The resource param is `medication`, matching the request/controller.)

- [ ] **Step 7: Add flash strings**

In `lang/en/flash.php`, replace the `medications` array with:

```php
    'medications' => [
        'added' => 'Medication added successfully.',
        'removed' => 'Medication removed successfully.',
        'created' => 'Medication created successfully.',
        'updated' => 'Medication updated successfully.',
        'deleted' => 'Medication deleted successfully.',
    ],
```

- [ ] **Step 8: Add nav label**

In `lang/en/nav.php`, add `'medications' => 'Medications',` immediately after the `'users' => 'Users',` line.

- [ ] **Step 9: Add catalog i18n namespace**

In `lang/en/medications.php`, add a `catalog` key to the returned array (alongside the existing keys, so the patient-picker strings are untouched):

```php
    'catalog' => [
        'index' => [
            'heading' => 'Medication Catalog',
            'new' => 'New Medication',
            'search_placeholder' => 'Search medications…',
            'filter_dose_form' => 'Dose Form',
            'column_name' => 'Name',
            'column_type' => 'Type',
            'column_dosage' => 'Dosage',
            'column_dose_form' => 'Form',
            'column_ndc' => 'NDC',
            'empty' => 'No medications in the catalog yet.',
            'record_label' => 'medications',
            'delete_confirm' => 'Delete this medication from the catalog? This cannot be undone.',
            'sort' => [
                'name' => 'Name',
                'type' => 'Type',
                'dose_form' => 'Form',
                'ndc' => 'NDC',
            ],
        ],
        'form' => [
            'new_title' => 'New Medication',
            'edit_title' => 'Edit :name',
            'label_name' => 'Name',
            'label_type' => 'Type',
            'label_dosage' => 'Dosage',
            'label_dose_form' => 'Dose Form',
            'label_ndc' => 'NDC',
            'submit' => 'Save Medication',
            'submitting' => 'Saving…',
        ],
    ],
```

- [ ] **Step 10: Run tests to verify they pass**

Run: `php artisan test --compact --filter=MedicationManagementTest`
Expected: PASS (10 passing). (The Inertia component assertions pass server-side even before the `.vue` files exist — those come in Tasks 4-5.)

- [ ] **Step 11: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreMedicationRequest.php app/Http/Requests/UpdateMedicationRequest.php app/Http/Controllers/MedicationController.php routes/web.php lang/en/flash.php lang/en/nav.php lang/en/medications.php tests/Feature/MedicationManagementTest.php
git commit -m "feat: add medication catalog controller, requests, and routes

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Index page + nav link

Build the catalog list page (mirrors `Users/Index.vue`) and add the sidebar link.

**Files:**
- Create: `resources/js/Pages/Medications/Index.vue`
- Modify: `resources/js/Layouts/DashboardLayout.vue` (Administration `children` array, ~line 41)
- Test: build + `tests/Feature/MedicationManagementTest.php` (already asserts the `Medications/Index` component and props)

**Interfaces:**
- Consumes: props `medications, search, sort_by, direction, filters, dose_form_options` from `MedicationController@index`; components `@/Components/SearchInput.vue`, `@/Components/SortDropdown.vue`, `@/Components/FilterDropdown.vue`; route `medications.destroy` for row delete.

- [ ] **Step 1: Add the sidebar nav link**

In `resources/js/Layouts/DashboardLayout.vue`, import the `Pill` icon in the existing `lucide-vue-next` import statement (add `Pill` to the destructured list alongside `Users`), then add to the Administration section `children` array (after the users entry):

```js
{ label: trans('nav.medications'), route: 'medications.index', icon: Pill },
```

- [ ] **Step 2: Create the index page**

Create `resources/js/Pages/Medications/Index.vue`:

```vue
<script setup>
import { computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SortDropdown from '@/Components/SortDropdown.vue'
import FilterDropdown from '@/Components/FilterDropdown.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.medications') },
    ]),
})

const props = defineProps({
    medications: {
        type: Object,
        required: true,
    },
    search: {
        type: String,
        default: '',
    },
    sort_by: {
        type: String,
        default: 'name',
    },
    direction: {
        type: String,
        default: 'asc',
    },
    filters: {
        type: Object,
        default: () => ({ dose_form: [] }),
    },
    dose_form_options: {
        type: Array,
        default: () => [],
    },
})

const sort_options = computed(() => [
    { label: trans('medications.catalog.index.sort.name'), value: 'name' },
    { label: trans('medications.catalog.index.sort.type'), value: 'type' },
    { label: trans('medications.catalog.index.sort.dose_form'), value: 'dose_form' },
    { label: trans('medications.catalog.index.sort.ndc'), value: 'ndc' },
])

function destroy(medication) {
    if (window.confirm(trans('medications.catalog.index.delete_confirm'))) {
        router.delete(route('medications.destroy', medication.id), { preserveScroll: true })
    }
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('medications.catalog.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ medications.total }}
                </span>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link
                    :href="route('medications.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('medications.catalog.index.new') }}
                </Link>
                <FilterDropdown
                    :label="$t('medications.catalog.index.filter_dose_form')"
                    param-name="dose_form"
                    :selected="props.filters.dose_form"
                    :options="props.dose_form_options"
                    :params="{ search: props.search || undefined, sort_by: props.sort_by, direction: props.direction }"
                    route-name="medications.index"
                />
                <SortDropdown
                    :sort-by="props.sort_by"
                    :direction="props.direction"
                    :options="sort_options"
                    :params="{ search: props.search || undefined, dose_form: props.filters.dose_form }"
                    route-name="medications.index"
                />
                <SearchInput
                    :model-value="props.search"
                    :params="{ sort_by: props.sort_by, direction: props.direction, dose_form: props.filters.dose_form }"
                    :placeholder="$t('medications.catalog.index.search_placeholder')"
                    route-name="medications.index"
                    class="w-full sm:w-72"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-card shadow-[0_1px_0_0_var(--color-border)]">
                    <tr class="text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('medications.catalog.index.column_name') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('medications.catalog.index.column_type') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('medications.catalog.index.column_dosage') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('medications.catalog.index.column_dose_form') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground lg:table-cell">{{ $t('medications.catalog.index.column_ndc') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="medications.data.length === 0">
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('medications.catalog.index.empty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(medication, index) in medications.data"
                        :key="medication.id"
                        class="border-l-2 border-transparent transition-colors hover:border-primary hover:bg-primary/5"
                        :class="index % 2 !== 0 ? 'bg-muted/20' : 'bg-card'"
                    >
                        <td class="px-6 py-4">
                            <Link
                                :href="route('medications.edit', medication.id)"
                                class="font-bold text-foreground hover:text-primary hover:underline"
                            >
                                {{ medication.name }}
                            </Link>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ medication.type }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground md:table-cell">{{ medication.dosage }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground md:table-cell">{{ medication.dose_form }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground lg:table-cell">{{ medication.ndc }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    as="button"
                                    type="button"
                                    :href="route('medications.edit', medication.id)"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                                >
                                    {{ $t('common.actions.edit') }}
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                                    @click="destroy(medication)"
                                >
                                    {{ $t('common.actions.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('common.pagination.summary', { from: medications.from, to: medications.to, total: medications.total, label: $t('medications.catalog.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="medications.prev_page_url"
                    :href="medications.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in medications.links.slice(1, -1)" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm font-bold"
                        :class="link.active
                            ? 'border-primary bg-primary text-white'
                            : 'border-border text-foreground hover:bg-muted/40'"
                    >
                        {{ link.label }}
                    </Link>
                    <span
                        v-else
                        class="px-2 py-1.5 text-sm text-muted-foreground"
                    >
                        {{ link.label }}
                    </span>
                </template>
                <Link
                    v-if="medications.next_page_url"
                    :href="medications.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Build to verify it compiles**

Run: `npm run build`
Expected: build succeeds with no errors referencing `Medications/Index.vue`.

- [ ] **Step 4: Run the backend index tests**

Run: `php artisan test --compact --filter=MedicationManagementTest`
Expected: PASS (still 10 passing).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Medications/Index.vue resources/js/Layouts/DashboardLayout.vue
git commit -m "feat: add medication catalog index page and nav link

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: Create/Edit form pages

Build the form wrapper (`Form.vue`) and the field partial (`Partials/Form.vue`), mirroring the Users equivalents.

**Files:**
- Create: `resources/js/Pages/Medications/Form.vue`
- Create: `resources/js/Pages/Medications/Partials/Form.vue`
- Test: build + `tests/Feature/MedicationManagementTest.php` (asserts `Medications/Form` for create & edit)

**Interfaces:**
- Consumes: props `medication?, dose_form_options` from `MedicationController@create/@edit`; routes `medications.index/store/update`.

- [ ] **Step 1: Create the form wrapper**

Create `resources/js/Pages/Medications/Form.vue`:

```vue
<script setup>
import { computed } from 'vue'
import { setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MedicationForm from '@/Pages/Medications/Partials/Form.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    medication: {
        type: Object,
        default: null,
    },
    dose_form_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.medication !== null)

setLayoutProps({
    breadcrumbs: computed(() => {
        if (isEditing.value) {
            return [
                { label: trans('nav.medications'), href: route('medications.index') },
                { label: trans('medications.catalog.form.edit_title', { name: props.medication.name }) },
            ]
        }
        return [
            { label: trans('nav.medications'), href: route('medications.index') },
            { label: trans('medications.catalog.form.new_title') },
        ]
    }),
})

const formAction = computed(() =>
    isEditing.value ? route('medications.update', props.medication.id) : route('medications.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <MedicationForm
        :action="formAction"
        :method="formMethod"
        :medication="medication"
        :cancel-href="route('medications.index')"
        :dose_form_options="dose_form_options"
    />
</template>
```

- [ ] **Step 2: Create the field partial**

Create `resources/js/Pages/Medications/Partials/Form.vue`:

```vue
<script setup>
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    medication: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        required: true,
    },
    dose_form_options: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    name: props.medication?.name ?? '',
    type: props.medication?.type ?? '',
    dosage: props.medication?.dosage ?? '',
    dose_form: props.medication?.dose_form ?? '',
    ndc: props.medication?.ndc ?? '',
})

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="submit">
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">{{ $t('medications.heading') }}</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                <!-- Name -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('medications.catalog.form.label_name') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.name }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('medications.catalog.form.label_type') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.type"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.type }"
                    />
                    <p v-if="form.errors.type" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.type }}</p>
                </div>

                <!-- Dosage -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('medications.catalog.form.label_dosage') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.dosage"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.dosage }"
                    />
                    <p v-if="form.errors.dosage" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dosage }}</p>
                </div>

                <!-- Dose Form -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('medications.catalog.form.label_dose_form') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <select
                        v-model="form.dose_form"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.dose_form }"
                    >
                        <option value="">{{ $t('common.actions.select_placeholder') }}</option>
                        <option v-for="opt in dose_form_options" :key="opt" :value="opt">
                            {{ $t('enums.dose_form.' + opt) }}
                        </option>
                    </select>
                    <p v-if="form.errors.dose_form" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.dose_form }}</p>
                </div>

                <!-- NDC -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('medications.catalog.form.label_ndc') }} <span class="text-vibrant-coral-500">*</span>
                    </label>
                    <input
                        v-model="form.ndc"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.ndc }"
                    />
                    <p v-if="form.errors.ndc" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.ndc }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                {{ $t('common.actions.cancel') }}
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? $t('medications.catalog.form.submitting') : $t('medications.catalog.form.submit') }}
            </button>
        </div>
    </form>
</template>
```

- [ ] **Step 3: Build to verify it compiles**

Run: `npm run build`
Expected: build succeeds with no errors referencing the Medications form files.

- [ ] **Step 4: Run the full medication suite**

Run: `php artisan test --compact --filter=Medication`
Expected: PASS (MedicationPolicyTest, MedicationCatalogModelTest, MedicationManagementTest all green).

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Medications/Form.vue resources/js/Pages/Medications/Partials/Form.vue
git commit -m "feat: add medication catalog create and edit form pages

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Full-suite regression check

Confirm the new `medications` permission resource did not disturb existing permission/seeding assertions.

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: PASS. Pay attention to `DataSeedingTest`, `UserPermissionNamesTest`, `AuthorizationTest`, and `NotePolicyTest` — all derive from `UserRole` dynamically and should remain green. If any hardcoded permission list surfaces, update it to include the four `*_medications` permissions.

- [ ] **Step 2: Lint check**

Run: `vendor/bin/pint --dirty --format agent`
Expected: no changes (already formatted).

- [ ] **Step 3: Final commit (only if Step 1 required a fix)**

```bash
git add -A
git commit -m "test: reconcile permission assertions with medications resource

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Self-Review Notes

- **Spec coverage:** routes (T3), model listing (T2), authorization/policy + all-roles grant (T1), requests incl. NDC unique+ignore (T3), controller 6 actions (T3), frontend Index/Form/Partial (T4-T5), nav link (T4), i18n `catalog` namespace + flash (T3), tests incl. authorization 403 + validation + soft-delete (T1/T3), full-suite regression for the enum change (T6). All spec sections mapped.
- **No Show page**, no `type` enum, no picker/`PatientMedication` changes — matches the approved spec's "Out of scope".
- **Type consistency:** resource param `medication` used consistently in routes, `UpdateMedicationRequest->route('medication')`, and controller signatures; prop names `medication`/`dose_form_options` consistent across controller, `Form.vue`, and the partial.
