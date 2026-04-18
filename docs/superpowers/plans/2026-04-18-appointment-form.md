# Appointment Create/Edit Form Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add create and edit forms for appointments nested under patients, with server-side staff conflict detection using the Actions + Services pattern.

**Architecture:** An `AppointmentConflictService` handles the overlap query in isolation; `BookAppointmentAction` and `UpdateAppointmentAction` call it, throw `ValidationException` on conflict, and persist the appointment. The controller is thin: validate, call action, redirect.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Pest 4, Tailwind v4. Existing patterns: `Form.vue` + `Partials/Form.vue`, `app/Actions/`, constructor-injected actions.

---

## File Map

| Action | Path | Responsibility |
|---|---|---|
| CREATE | `app/Services/AppointmentConflictService.php` | Overlap query — returns conflicting users |
| CREATE | `app/Actions/BookAppointmentAction.php` | Conflict check → create appointment → attach staff |
| CREATE | `app/Actions/UpdateAppointmentAction.php` | Conflict check → update appointment → sync staff |
| CREATE | `app/Http/Requests/StoreAppointmentRequest.php` | Validate store payload incl. primary-count rule |
| CREATE | `app/Http/Requests/UpdateAppointmentRequest.php` | Same rules for update |
| CREATE | `app/Http/Controllers/AppointmentController.php` | Thin: render forms, call actions, redirect |
| MODIFY | `routes/web.php` | Add nested `patients.appointments` resource |
| CREATE | `resources/js/Pages/Appointments/Form.vue` | Page: create/edit mode, back link, layout |
| CREATE | `resources/js/Pages/Appointments/Partials/AppointmentForm.vue` | All form fields incl. dynamic staff picker |
| MODIFY | `resources/js/Pages/Patients/Show.vue` | Add "New Appointment" button + per-row edit link |
| CREATE | `tests/Feature/AppointmentConflictServiceTest.php` | Unit-level service tests (overlap math) |
| CREATE | `tests/Feature/AppointmentControllerTest.php` | Happy path + conflict rejection |

---

## Task 1: AppointmentConflictService (TDD)

**Files:**
- Create: `tests/Feature/AppointmentConflictServiceTest.php`
- Create: `app/Services/AppointmentConflictService.php`

- [ ] **Step 1: Write the failing tests**

```bash
php artisan make:test --pest AppointmentConflictServiceTest
```

Replace the generated file content with:

```php
<?php

use App\Enums\AppointmentRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns no conflicts when appointments are adjacent (end equals next start)', function () {
    $service = new AppointmentConflictService();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(0);
});

it('returns conflicting user when times partially overlap', function () {
    $service = new AppointmentConflictService();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($staff->id);
});

it('returns conflicting user when new appointment fully contains an existing one', function () {
    $service = new AppointmentConflictService();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '10:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '11:00', [$staff->id]);

    expect($conflicts)->toHaveCount(1);
});

it('excludes the appointment being edited from conflict check', function () {
    $service = new AppointmentConflictService();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '10:00', [$staff->id], $appointment->id);

    expect($conflicts)->toHaveCount(0);
});

it('only returns conflicting staff, not free staff', function () {
    $service = new AppointmentConflictService();
    $busyStaff = User::factory()->withRole(UserRole::Staff)->create();
    $freeStaff = User::factory()->withRole(UserRole::Staff)->create();

    Appointment::factory()
        ->withProvider($busyStaff, AppointmentRole::Primary)
        ->create(['date' => '2026-05-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $conflicts = $service->findConflicts('2026-05-01', '10:00', '11:00', [$busyStaff->id, $freeStaff->id]);

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($busyStaff->id);
});

it('returns no conflicts when there are no appointments on that date', function () {
    $service = new AppointmentConflictService();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $conflicts = $service->findConflicts('2026-05-01', '09:00', '10:00', [$staff->id]);

    expect($conflicts)->toHaveCount(0);
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact tests/Feature/AppointmentConflictServiceTest.php
```

Expected: FAIL — `App\Services\AppointmentConflictService` not found.

- [ ] **Step 3: Create the service**

```bash
php artisan make:class Services/AppointmentConflictService
```

Replace the generated file with:

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AppointmentConflictService
{
    /**
     * @param  array<int>  $userIds
     * @return Collection<int, User>
     */
    public function findConflicts(
        string $date,
        string $start_time,
        string $end_time,
        array $userIds,
        ?int $excludeAppointmentId = null
    ): Collection {
        return User::whereIn('id', $userIds)
            ->whereHas('appointments', function ($query) use ($date, $start_time, $end_time, $excludeAppointmentId) {
                $query->where('date', $date)
                    ->where('start_time', '<', $end_time)
                    ->where('end_time', '>', $start_time)
                    ->when($excludeAppointmentId, fn ($q) => $q->where('appointments.id', '!=', $excludeAppointmentId));
            })
            ->get();
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test --compact tests/Feature/AppointmentConflictServiceTest.php
```

Expected: All 6 tests pass.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/AppointmentConflictService.php tests/Feature/AppointmentConflictServiceTest.php
git commit -m "feat: add AppointmentConflictService with tests"
```

---

## Task 2: Form Requests

**Files:**
- Create: `app/Http/Requests/StoreAppointmentRequest.php`
- Create: `app/Http/Requests/UpdateAppointmentRequest.php`

- [ ] **Step 1: Generate the form requests**

```bash
php artisan make:request StoreAppointmentRequest
php artisan make:request UpdateAppointmentRequest
```

- [ ] **Step 2: Implement StoreAppointmentRequest**

Replace `app/Http/Requests/StoreAppointmentRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'staff' => ['required', 'array', 'min:1'],
            'staff.*.user_id' => ['required', 'exists:users,id'],
            'staff.*.role' => ['required', Rule::in(array_column(AppointmentRole::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $primaryCount = collect($this->input('staff', []))
                ->filter(fn ($s) => ($s['role'] ?? '') === AppointmentRole::Primary->value)
                ->count();

            if ($primaryCount !== 1) {
                $validator->errors()->add('staff', 'Exactly one staff member must be assigned as Primary.');
            }
        });
    }
}
```

- [ ] **Step 3: Implement UpdateAppointmentRequest**

Replace `app/Http/Requests/UpdateAppointmentRequest.php` with an identical body (same rules, same `withValidator`):

```php
<?php

namespace App\Http\Requests;

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'staff' => ['required', 'array', 'min:1'],
            'staff.*.user_id' => ['required', 'exists:users,id'],
            'staff.*.role' => ['required', Rule::in(array_column(AppointmentRole::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $primaryCount = collect($this->input('staff', []))
                ->filter(fn ($s) => ($s['role'] ?? '') === AppointmentRole::Primary->value)
                ->count();

            if ($primaryCount !== 1) {
                $validator->errors()->add('staff', 'Exactly one staff member must be assigned as Primary.');
            }
        });
    }
}
```

- [ ] **Step 4: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/StoreAppointmentRequest.php app/Http/Requests/UpdateAppointmentRequest.php
git commit -m "feat: add StoreAppointmentRequest and UpdateAppointmentRequest"
```

---

## Task 3: BookAppointmentAction and UpdateAppointmentAction

**Files:**
- Create: `app/Actions/BookAppointmentAction.php`
- Create: `app/Actions/UpdateAppointmentAction.php`

- [ ] **Step 1: Create BookAppointmentAction**

```php
<?php

namespace App\Actions;

use App\Enums\AppointmentRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Validation\ValidationException;

class BookAppointmentAction
{
    public function __construct(private AppointmentConflictService $conflictService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Patient $patient, array $validated): Appointment
    {
        $userIds = array_column($validated['staff'], 'user_id');

        $conflicts = $this->conflictService->findConflicts(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $userIds,
        );

        if ($conflicts->isNotEmpty()) {
            $names = $conflicts->map(fn (User $u) => "{$u->first_name} {$u->last_name}")->join(', ');
            throw ValidationException::withMessages([
                'staff' => "The following staff have conflicting appointments: {$names}.",
            ]);
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['staff'] as $entry) {
            $user = User::findOrFail($entry['user_id']);
            $appointment->attachProvider($user, AppointmentRole::from($entry['role']));
        }

        return $appointment;
    }
}
```

- [ ] **Step 2: Create UpdateAppointmentAction**

```php
<?php

namespace App\Actions;

use App\Enums\AppointmentRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentAction
{
    public function __construct(private AppointmentConflictService $conflictService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Appointment $appointment, array $validated): Appointment
    {
        $userIds = array_column($validated['staff'], 'user_id');

        $conflicts = $this->conflictService->findConflicts(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $userIds,
            $appointment->id,
        );

        if ($conflicts->isNotEmpty()) {
            $names = $conflicts->map(fn (User $u) => "{$u->first_name} {$u->last_name}")->join(', ');
            throw ValidationException::withMessages([
                'staff' => "The following staff have conflicting appointments: {$names}.",
            ]);
        }

        $appointment->update([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $appointment->users()->detach();

        foreach ($validated['staff'] as $entry) {
            $user = User::findOrFail($entry['user_id']);
            $appointment->attachProvider($user, AppointmentRole::from($entry['role']));
        }

        return $appointment->fresh();
    }
}
```

- [ ] **Step 3: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/BookAppointmentAction.php app/Actions/UpdateAppointmentAction.php
git commit -m "feat: add BookAppointmentAction and UpdateAppointmentAction"
```

---

## Task 4: AppointmentController, Routes, and Feature Tests (TDD)

**Files:**
- Create: `tests/Feature/AppointmentControllerTest.php`
- Create: `app/Http/Controllers/AppointmentController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Add the route**

Open `routes/web.php` and add the nested resource route. The full file should look like:

```php
<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
Route::resource('patients.appointments', AppointmentController::class)
    ->only(['create', 'store', 'edit', 'update'])
    ->scoped();
Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);

Route::middleware(['auth', 'verified'])->group(function () {
    // Authenticated routes (patient, user, etc.) will be registered here.
    // Prefer Route::resource / apiResource with implicit model binding.
});
```

- [ ] **Step 2: Verify the routes registered correctly**

```bash
php artisan route:list --name=patients.appointments
```

Expected output (4 routes):

```
GET|HEAD   patients/{patient}/appointments/create   patients.appointments.create  AppointmentController@create
POST       patients/{patient}/appointments           patients.appointments.store   AppointmentController@store
GET|HEAD   patients/{patient}/appointments/{appointment}/edit  patients.appointments.edit  AppointmentController@edit
PUT|PATCH  patients/{patient}/appointments/{appointment}  patients.appointments.update  AppointmentController@update
```

- [ ] **Step 3: Write the failing feature tests**

```bash
php artisan make:test --pest AppointmentControllerTest
```

Replace the generated file with:

```php
<?php

use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStaffPayload(User $staff, string $role = 'Primary'): array
{
    return [
        'date' => '2026-06-01',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => AppointmentStatus::Scheduled->value,
        'reason' => 'Annual checkup',
        'notes' => null,
        'staff' => [
            ['user_id' => $staff->id, 'role' => $role],
        ],
    ];
}

it('renders the create form for an appointment', function () {
    $patient = Patient::factory()->create();

    $response = $this->get(route('patients.appointments.create', $patient));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Form')
            ->has('patient')
            ->has('status_options')
            ->has('role_options')
            ->has('staff_options')
        );
});

it('creates an appointment and redirects to patient show', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    $response = $this->post(
        route('patients.appointments.store', $patient),
        makeStaffPayload($staff)
    );

    $response->assertRedirect(route('patients.show', $patient));
    expect(Appointment::count())->toBe(1);
    expect(Appointment::first()->users()->count())->toBe(1);
});

it('rejects store when a staff member has a conflicting appointment', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    // Staff already has 09:00–10:30 on the same date
    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    $response = $this->post(
        route('patients.appointments.store', $patient),
        makeStaffPayload($staff)
    );

    $response->assertSessionHasErrors('staff');
    expect(Appointment::count())->toBe(1); // no new appointment created
});

it('renders the edit form for an appointment', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

    $response = $this->get(route('patients.appointments.edit', [$patient, $appointment]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Form')
            ->has('appointment')
            ->has('patient')
        );
});

it('updates an appointment and redirects to patient show', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    $payload = makeStaffPayload($staff);
    $payload['reason'] = 'Updated reason';

    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        $payload
    );

    $response->assertRedirect(route('patients.show', $patient));
    expect($appointment->fresh()->reason)->toBe('Updated reason');
});

it('excludes the appointment being edited from the conflict check', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:00']);

    // Updating with the exact same time slot — should not conflict with itself
    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        makeStaffPayload($staff)
    );

    $response->assertRedirect(route('patients.show', $patient));
});

it('rejects update when a staff member conflicts with a different appointment', function () {
    $patient = Patient::factory()->create();
    $staff = User::factory()->withRole(UserRole::Staff)->create();

    // Staff has a DIFFERENT appointment that overlaps with our update payload
    Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['date' => '2026-06-01', 'start_time' => '09:00', 'end_time' => '10:30']);

    // The appointment we are editing (different time, no conflict on its own)
    $appointment = Appointment::factory()
        ->withProvider($staff, AppointmentRole::Primary)
        ->create(['patient_id' => $patient->id, 'date' => '2026-06-02', 'start_time' => '11:00', 'end_time' => '12:00']);

    // Trying to move it to 2026-06-01 10:00–11:00, which overlaps the first appointment
    $payload = makeStaffPayload($staff);

    $response = $this->put(
        route('patients.appointments.update', [$patient, $appointment]),
        $payload
    );

    $response->assertSessionHasErrors('staff');
});
```

- [ ] **Step 4: Run tests to confirm they fail**

```bash
php artisan test --compact tests/Feature/AppointmentControllerTest.php
```

Expected: FAIL — `AppointmentController` not found.

- [ ] **Step 5: Generate and implement the controller**

```bash
php artisan make:controller AppointmentController
```

Replace the generated file with:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\BookAppointmentAction;
use App\Actions\UpdateAppointmentAction;
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        private BookAppointmentAction $bookAction,
        private UpdateAppointmentAction $updateAction,
    ) {}

    public function create(Patient $patient): Response
    {
        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('user'),
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
            'staff_options' => User::staff()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreAppointmentRequest $request, Patient $patient): RedirectResponse
    {
        $this->bookAction->execute($patient, $request->validated());

        return redirect()->route('patients.show', $patient);
    }

    public function edit(Patient $patient, Appointment $appointment): Response
    {
        $appointment->load('users');

        return Inertia::render('Appointments/Form', [
            'patient' => $patient->load('user'),
            'appointment' => $appointment,
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
            'staff_options' => User::staff()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Patient $patient, Appointment $appointment): RedirectResponse
    {
        $this->updateAction->execute($appointment, $request->validated());

        return redirect()->route('patients.show', $patient);
    }
}
```

- [ ] **Step 6: Run all tests to confirm they pass**

```bash
php artisan test --compact tests/Feature/AppointmentControllerTest.php
```

Expected: All 7 tests pass.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/AppointmentController.php routes/web.php tests/Feature/AppointmentControllerTest.php
git commit -m "feat: add AppointmentController with create/store/edit/update and feature tests"
```

---

## Task 5: Vue Pages — Appointments/Form.vue and Partials/AppointmentForm.vue

**Files:**
- Create: `resources/js/Pages/Appointments/Form.vue`
- Create: `resources/js/Pages/Appointments/Partials/AppointmentForm.vue`

- [ ] **Step 1: Create the page directory**

```bash
mkdir -p resources/js/Pages/Appointments/Partials
```

- [ ] **Step 2: Create Form.vue**

Create `resources/js/Pages/Appointments/Form.vue`:

```vue
<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import AppointmentForm from '@/Pages/Appointments/Partials/AppointmentForm.vue'

defineOptions({ layout: DashboardLayout })

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    appointment: {
        type: Object,
        default: null,
    },
    status_options: {
        type: Array,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
    staff_options: {
        type: Array,
        required: true,
    },
})

const isEditing = computed(() => props.appointment !== null)

setLayoutProps({
    title: computed(() =>
        isEditing.value ? 'Edit Appointment' : 'New Appointment'
    ),
})

const backHref = computed(() => route('patients.show', props.patient.id))

const formAction = computed(() =>
    isEditing.value
        ? route('patients.appointments.update', [props.patient.id, props.appointment.id])
        : route('patients.appointments.store', props.patient.id)
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))
</script>

<template>
    <div class="grid gap-6">
        <div>
            <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
                ← Back to {{ patient.user.first_name }} {{ patient.user.last_name }}
            </Link>
        </div>

        <AppointmentForm
            :action="formAction"
            :method="formMethod"
            :appointment="appointment"
            :cancel-href="backHref"
            :status_options="status_options"
            :role_options="role_options"
            :staff_options="staff_options"
        />
    </div>
</template>
```

- [ ] **Step 3: Create Partials/AppointmentForm.vue**

Create `resources/js/Pages/Appointments/Partials/AppointmentForm.vue`:

```vue
<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import DatePicker from '@/Components/ui/DatePicker.vue'

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        required: true,
    },
    appointment: {
        type: Object,
        default: null,
    },
    cancelHref: {
        type: String,
        required: true,
    },
    status_options: {
        type: Array,
        required: true,
    },
    role_options: {
        type: Array,
        required: true,
    },
    staff_options: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    date: props.appointment?.date?.substring(0, 10) ?? '',
    start_time: props.appointment?.start_time?.substring(0, 5) ?? '',
    end_time: props.appointment?.end_time?.substring(0, 5) ?? '',
    status: props.appointment?.status ?? '',
    reason: props.appointment?.reason ?? '',
    notes: props.appointment?.notes ?? '',
    staff: props.appointment?.users?.map((u) => ({
        user_id: u.id,
        role: u.pivot.role,
    })) ?? [{ user_id: '', role: 'Assistant' }],
})

function addStaff() {
    form.staff.push({ user_id: '', role: 'Assistant' })
}

function removeStaff(index) {
    form.staff.splice(index, 1)
}

function submit() {
    form[props.method](props.action)
}
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-6">
        <!-- Scheduling -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Scheduling</h2>
            </div>
            <div class="grid gap-5 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Date -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        v-model="form.date"
                        placeholder="Select date"
                        :class="{ 'ring-2 ring-red-400 rounded-lg': form.errors.date }"
                    />
                    <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
                </div>

                <!-- Start Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Start Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.start_time"
                        type="time"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.start_time }"
                    />
                    <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
                </div>

                <!-- End Time -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        End Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.end_time"
                        type="time"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.end_time }"
                    />
                    <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.status }"
                    >
                        <option value="">Select…</option>
                        <option v-for="opt in status_options" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Details</h2>
            </div>
            <div class="grid gap-5 px-6 py-5">
                <!-- Reason -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Reason <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.reason"
                        type="text"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-red-400': form.errors.reason }"
                        placeholder="Reason for visit"
                    />
                    <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                </div>

                <!-- Notes -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        Notes
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        placeholder="Optional notes…"
                    />
                </div>
            </div>
        </div>

        <!-- Staff -->
        <div class="rounded-xl border border-border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 class="font-bold text-foreground">Staff</h2>
                <button
                    type="button"
                    class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    @click="addStaff"
                >
                    + Add Staff
                </button>
            </div>
            <div class="grid gap-3 px-6 py-5">
                <div
                    v-for="(entry, index) in form.staff"
                    :key="index"
                    class="flex items-center gap-3"
                >
                    <select
                        v-model="entry.user_id"
                        class="flex-1 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option value="">Select staff…</option>
                        <option v-for="opt in staff_options" :key="opt.id" :value="opt.id">
                            {{ opt.last_name }}, {{ opt.first_name }}
                        </option>
                    </select>
                    <select
                        v-model="entry.role"
                        class="w-36 rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                    >
                        <option v-for="role in role_options" :key="role" :value="role">{{ role }}</option>
                    </select>
                    <button
                        v-if="form.staff.length > 1"
                        type="button"
                        class="shrink-0 rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50"
                        @click="removeStaff(index)"
                    >
                        Remove
                    </button>
                </div>

                <!-- Conflict / staff error banner -->
                <div
                    v-if="form.errors.staff"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ form.errors.staff }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
            >
                Cancel
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90 disabled:opacity-50"
            >
                {{ form.processing ? 'Saving…' : 'Save Appointment' }}
            </button>
        </div>
    </form>
</template>
```

- [ ] **Step 4: Build assets and verify no compile errors**

```bash
npm run build
```

Expected: Build completes with no errors. If you see "Unable to locate file in Vite manifest", the build step resolves it.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Appointments/
git commit -m "feat: add Appointments/Form.vue and AppointmentForm partial"
```

---

## Task 6: Add Appointment Links to Patients/Show.vue

**Files:**
- Modify: `resources/js/Pages/Patients/Show.vue`

The appointments section in Show.vue already exists. This task adds a "New Appointment" button in the section header and an "Edit" link on each appointment row.

- [ ] **Step 1: Open Show.vue and find the appointments section**

Look for the element that renders the appointments list (it will have `patient.appointments` in a `v-for`). The section header and row actions need two additions:

1. In the appointments card header — add a "New Appointment" `<Link>` next to the heading.
2. In each appointment row — add an "Edit" `<Link>`.

- [ ] **Step 2: Add "New Appointment" button to the appointments section header**

Find the appointments card header (it has a heading like "Appointments"). Change it from:

```vue
<div class="border-b border-border px-6 py-4">
    <h2 class="font-bold text-foreground">Appointments</h2>
</div>
```

to:

```vue
<div class="flex items-center justify-between border-b border-border px-6 py-4">
    <h2 class="font-bold text-foreground">Appointments</h2>
    <Link
        :href="route('patients.appointments.create', patient.id)"
        class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white hover:bg-primary/90"
    >
        + New Appointment
    </Link>
</div>
```

- [ ] **Step 3: Add "Edit" link to each appointment row**

Find the `v-for` loop that renders appointment rows. In each row, add an edit link. For example, if the row currently has a status badge or similar content on the right, append:

```vue
<Link
    :href="route('patients.appointments.edit', [patient.id, appointment.id])"
    class="text-xs font-bold text-primary hover:underline"
>
    Edit
</Link>
```

The exact placement depends on the current row markup — put it at the trailing end of each row.

- [ ] **Step 4: Build assets and do a manual smoke test**

```bash
npm run build
```

Navigate to a patient's show page and verify:
- "New Appointment" button appears in the Appointments section header.
- Clicking it loads the create form with the correct patient name in the back link.
- Each appointment row has an "Edit" link that loads the edit form pre-filled.
- Submitting the form with a conflicting staff member shows the red error banner.
- Submitting with no conflict saves and redirects back to the patient show page.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Patients/Show.vue
git commit -m "feat: add New Appointment and Edit links to patient show page"
```

---

## Task 7: Run the Full Test Suite

- [ ] **Step 1: Run all tests**

```bash
php artisan test --compact
```

Expected: All tests pass, including the two new test files.

- [ ] **Step 2: If any tests fail, investigate and fix before proceeding**

Common failure modes:
- `patient_id` mismatch: The `AppointmentFactory` sets `patient_id = user->id`. The controller tests create appointments via `Appointment::factory()->create(['patient_id' => $patient->id])` where `$patient->id` is the Patient model's primary key. If scoped route binding fails, check that the appointment's `patient_id` matches.
- Inertia assertion failures: Ensure the Inertia test helpers are available (`assertInertia`). If not, install: `composer require inertiajs/inertia-laravel --dev` is already in composer — check `tests/TestCase.php` for any needed setup.

- [ ] **Step 3: Final commit if any fixes were made**

```bash
vendor/bin/pint --dirty --format agent
git add -p
git commit -m "fix: resolve test failures after full suite run"
```
