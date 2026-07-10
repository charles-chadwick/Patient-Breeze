# Appointment Listing Modal Edit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** On the patient show page, remove the Notes column from the appointments table and make the Edit action open the appointment form in a modal instead of navigating to a full page.

**Architecture:** Mirror the existing `MedicationsBlock → MedicationModal → MedicationForm` convention. The shared `AppointmentForm` gains an embeddable mode (external submit button + `success` emit); a new `AppointmentModal` wraps it in the `ui/dialog`; `Patients/Show.vue` opens the modal for the selected row. The backend passes the enum options the form needs.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3 (`<script setup>`), Tailwind v4, reka-ui dialog, Pest v4 (feature + browser tests).

## Global Constraints

- Vue: `<script setup>`, single root element, `snake_case` local variables, `camelCase` functions (matches sibling components).
- Reuse existing components/patterns before writing new ones; follow the Medication modal pattern exactly.
- Run `vendor/bin/pint --dirty --format agent` before committing any PHP change.
- Every change is programmatically tested; run the minimum tests with `php artisan test --compact --filter=...`.
- Do not add dependencies. Do not create documentation files.
- i18n: no hardcoded UI copy — use `$t(...)` keys.

---

### Task 1: Backend — expose appointment form options on the patient chart

**Files:**
- Modify: `app/Http/Controllers/PatientController.php` (the `show()` method, ~lines 95-110)
- Test: `tests/Feature/AppointmentControllerTest.php`

**Interfaces:**
- Produces: two new Inertia props on `Patients/Show`: `status_options` (array of `AppointmentStatus` values) and `role_options` (array of `AppointmentRole` values) — same shape as `AppointmentController::sharedProps()`.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/AppointmentControllerTest.php` (append at end of file):

```php
it('exposes appointment form options on the patient chart', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('patients.show', $patient))
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->has('status_options')
            ->has('role_options')
        );
});
```

If `UserRole`/`User`/`Patient` are not already imported at the top of the test file, confirm the existing tests' imports cover them (they do — the file already uses these factories).

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="exposes appointment form options"`
Expected: FAIL — `status_options` prop missing.

- [ ] **Step 3: Add the enum imports**

In `app/Http/Controllers/PatientController.php`, add to the `use App\Enums\...` block (alphabetical, near the top with the other enum imports):

```php
use App\Enums\AppointmentRole;
use App\Enums\AppointmentStatus;
```

- [ ] **Step 4: Pass the options in `show()`**

In the `Inertia::render('Patients/Show', [ ... ])` array inside `show()`, add these two entries (place them right after the `'appointment_search' => $search,` line):

```php
            'status_options' => array_column(AppointmentStatus::cases(), 'value'),
            'role_options' => array_column(AppointmentRole::cases(), 'value'),
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter="exposes appointment form options"`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/PatientController.php tests/Feature/AppointmentControllerTest.php
git commit -m "feat: expose appointment form options on patient chart"
```

---

### Task 2: Make `AppointmentForm` embeddable in a modal

**Files:**
- Modify: `resources/js/Pages/Appointments/Partials/AppointmentForm.vue`

**Interfaces:**
- Consumes: nothing new.
- Produces: `AppointmentForm` accepts `formId` (String, default `'appointment-form'`), `showActions` (Boolean, default `true`), makes `cancelHref` optional, and emits `success` after a successful submit. Full-page usage (`Appointments/Form.vue`) is unchanged because the defaults preserve current behavior.

- [ ] **Step 1: Update the props block**

In `<script setup>`, replace the `cancelHref` prop definition and add the two new props. The `defineProps` object becomes:

```js
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
    formId: {
        type: String,
        default: 'appointment-form',
    },
    showActions: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['success'])
```

- [ ] **Step 2: Emit `success` on submit and preserve scroll**

Replace the `submit()` function with:

```js
function submit() {
    form[props.method](props.action, {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
```

- [ ] **Step 3: Bind the form id**

In `<template>`, change the opening form tag from `<form @submit.prevent="submit" class="grid gap-6">` to:

```html
    <form :id="formId" @submit.prevent="submit" class="grid gap-6">
```

- [ ] **Step 4: Add a testid to the notes textarea**

On the notes `<textarea>` (the one bound to `form.notes`), add a `data-testid` attribute so the browser test can target it:

```html
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        data-testid="appointment-notes-input"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/50"
                        :class="{ 'border-vibrant-coral-400': form.errors.notes }"
                        :placeholder="$t('appointments.form.placeholder_notes')"
                    />
```

- [ ] **Step 5: Gate the action bar behind `showActions`**

Wrap the final actions `<div>` (the one containing the Cancel `<Link>` and the submit `<button>`) with `v-if="showActions"`:

```html
        <!-- Actions -->
        <div v-if="showActions" class="flex items-center justify-end gap-3">
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
                {{ form.processing ? $t('appointments.form.submitting') : $t('appointments.form.submit') }}
            </button>
        </div>
```

- [ ] **Step 6: Verify the full-page form still renders (regression)**

Run: `php artisan test --compact --filter=AppointmentControllerTest`
Expected: PASS (the create/edit page render tests still pass — defaults unchanged).

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Appointments/Partials/AppointmentForm.vue
git commit -m "feat: make AppointmentForm embeddable in a modal"
```

---

### Task 3: Add an `edit_hint` translation key

**Files:**
- Modify: `lang/en/appointments.php` (the `'form' => [ ... ]` block)

**Interfaces:**
- Produces: translation key `appointments.form.edit_hint`, used by the modal's `DialogDescription` (reka-ui dialogs expect a description for accessibility).

- [ ] **Step 1: Add the key**

In `lang/en/appointments.php`, inside the `'form' => [` array, add after `'edit_title' => 'Edit Appointment',`:

```php
        'edit_hint' => 'Update the appointment details below.',
```

- [ ] **Step 2: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add lang/en/appointments.php
git commit -m "feat: add appointment edit modal hint copy"
```

---

### Task 4: Create `AppointmentModal`

**Files:**
- Create: `resources/js/Components/AppointmentModal.vue`

**Interfaces:**
- Consumes: `AppointmentForm` (`formId`, `showActions`, `success` emit from Task 2); `ui/dialog` components; `appointments.form.edit_title` / `appointments.form.edit_hint`.
- Produces: `<AppointmentModal>` with props `open` (Boolean), `patientId` (Number), `appointment` (Object|null), `status_options` (Array), `role_options` (Array); emits `update:open` and `saved`. Renders the edit form (PUT to `patients.appointments.update`) with an external Save button in the footer.

- [ ] **Step 1: Create the component**

Create `resources/js/Components/AppointmentModal.vue`:

```vue
<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import AppointmentForm from '@/Pages/Appointments/Partials/AppointmentForm.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    patientId: {
        type: Number,
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
})

const emit = defineEmits(['update:open', 'saved'])

function handleOpenUpdate(value) {
    emit('update:open', value)
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ $t('appointments.form.edit_title') }}</DialogTitle>
                <DialogDescription>{{ $t('appointments.form.edit_hint') }}</DialogDescription>
            </DialogHeader>

            <AppointmentForm
                v-if="appointment"
                :key="appointment.id"
                :action="route('patients.appointments.update', [patientId, appointment.id])"
                method="put"
                :appointment="appointment"
                :status_options="status_options"
                :role_options="role_options"
                form-id="appointment-form"
                :show-actions="false"
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
                    form="appointment-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('appointments.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 2: Build assets to catch syntax errors**

Run: `npm run build`
Expected: build completes with no errors referencing `AppointmentModal.vue`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/AppointmentModal.vue
git commit -m "feat: add AppointmentModal for editing from the listing"
```

---

### Task 5: Wire the modal into `Patients/Show.vue` and remove the Notes column

**Files:**
- Modify: `resources/js/Pages/Patients/Show.vue`

**Interfaces:**
- Consumes: `AppointmentModal` (Task 4); `status_options` / `role_options` props (Task 1).
- Produces: the appointments table no longer has a Notes column; the Edit action opens `AppointmentModal` for the selected appointment.

- [ ] **Step 1: Import the modal and add reactive state**

In `<script setup>`, add the import alongside the other component imports:

```js
import AppointmentModal from '@/Components/AppointmentModal.vue'
```

Add the two new props inside `defineProps({ ... })` (place after the `appointment_search` prop):

```js
    status_options: {
        type: Array,
        default: () => [],
    },
    role_options: {
        type: Array,
        default: () => [],
    },
```

Add modal state near the other `ref(...)` declarations (after `const records_tab = ref('appointments')`):

```js
const appointment_modal_open = ref(false)
const editing_appointment = ref(null)

function editAppointment(appointment) {
    editing_appointment.value = appointment
    appointment_modal_open.value = true
}
```

- [ ] **Step 2: Remove the Notes column header**

Delete this `<th>` from the table head:

```html
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('patients.show.column_notes') }}</th>
```

- [ ] **Step 3: Remove the Notes column cell**

Delete this `<td>` from the table body row:

```html
                        <td class="px-6 py-3 text-muted-foreground">{{ appointment.notes ?? $t('common.placeholders.em_dash') }}</td>
```

- [ ] **Step 4: Replace the Edit link with a modal trigger button**

Replace the Edit `<Link>` (inside the last `<td>` of the row) with:

```html
                        <td class="px-6 py-3">
                            <button
                                type="button"
                                data-testid="appointment-edit-button"
                                @click="editAppointment(appointment)"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                            >
                                {{ $t('common.actions.edit') }}
                            </button>
                        </td>
```

- [ ] **Step 5: Render the modal**

Add the modal just before the closing `</div>` of the `v-if="records_tab === 'appointments'"` block (right after the pagination `<div>`), so it lives within the appointments panel:

```html
            <AppointmentModal
                v-model:open="appointment_modal_open"
                :patient-id="patient.id"
                :appointment="editing_appointment"
                :status_options="status_options"
                :role_options="role_options"
            />
```

- [ ] **Step 6: Build assets**

Run: `npm run build`
Expected: build succeeds.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Patients/Show.vue
git commit -m "feat: edit appointments via modal and drop notes column from listing"
```

---

### Task 6: Browser test for the modal edit flow

**Files:**
- Create: `tests/Browser/AppointmentModalTest.php`

**Interfaces:**
- Consumes: `data-testid="records-tab-appointments"` (already on the page), `data-testid="appointment-edit-button"` (Task 5), `data-testid="appointment-notes-input"` (Task 2).

- [ ] **Step 1: Write the browser test**

Create `tests/Browser/AppointmentModalTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    foreach (UserRole::cases() as $role) {
        Role::findOrCreate($role->value);
    }
});

test('a staff user can edit an appointment via the listing modal', function () {
    $user = User::factory()->withRole(UserRole::Staff)->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->create([
        'notes' => 'Original note',
    ]);

    $this->actingAs($user);

    $page = visit(route('patients.show', $patient));

    $page->assertNoJavascriptErrors()
        ->click('[data-testid="appointment-edit-button"]')
        ->assertSee('Edit Appointment')
        ->fill('[data-testid="appointment-notes-input"]', 'Updated note')
        ->click('button[type="submit"][form="appointment-form"]')
        ->assertNoJavascriptErrors();

    expect($appointment->fresh()->notes)->toBe('Updated note');
})->group('browser');
```

- [ ] **Step 2: Verify the Appointment factory exists and supports these fields**

Run: `php artisan test --compact --filter=AppointmentModalTest`
Expected: PASS. If the factory does not set required columns (e.g. `date`, `start_time`, `end_time`, `reason`, `status`), the factory already provides them — the existing `AppointmentControllerTest` and `AppointmentTest` rely on `Appointment::factory()`. If the test fails on a missing/invalid field rather than the assertion, inspect `database/factories/AppointmentFactory.php` and pass the needed attributes explicitly in the `create([...])` call.

- [ ] **Step 3: Commit**

```bash
git add tests/Browser/AppointmentModalTest.php
git commit -m "test: browser coverage for appointment listing modal edit"
```

---

### Task 7: Full verification

- [ ] **Step 1: Run the affected test suites**

Run: `php artisan test --compact --filter="AppointmentControllerTest|AppointmentModalTest|PatientChartTest|PatientRecordsTabsTest"`
Expected: all PASS.

- [ ] **Step 2: Final asset build**

Run: `npm run build`
Expected: succeeds with no errors.

- [ ] **Step 3: Manual smoke (optional, if a dev server is running)**

Visit a patient with appointments → Appointments tab → confirm no Notes column, click Edit → modal opens with Notes populated → change and Save → modal closes and value persists.
</content>
</invoke>
