# Appointment Create/Edit Form — Design Spec

**Date:** 2026-04-18

## Overview

Add create and edit forms for appointments, nested under patients. A `AppointmentConflictService` detects staff scheduling overlaps; `BookAppointmentAction` and `UpdateAppointmentAction` orchestrate validation, conflict checking, and persistence. The controller stays thin.

---

## Routes

Appointments are nested under patients, matching the existing `patients.show` UI:

```
GET  patients/{patient}/appointments/create           → AppointmentController@create
POST patients/{patient}/appointments                  → AppointmentController@store
GET  patients/{patient}/appointments/{appointment}/edit → AppointmentController@edit
PUT  patients/{patient}/appointments/{appointment}    → AppointmentController@update
```

Add to `routes/web.php` using `Route::resource('patients.appointments', ...)` scoped to `['create', 'store', 'edit', 'update']`.

---

## Form Requests

### `StoreAppointmentRequest`
| Field | Rules |
|---|---|
| `date` | required, date |
| `start_time` | required, date_format:H:i |
| `end_time` | required, date_format:H:i, after:start_time |
| `status` | required, enum:AppointmentStatus |
| `reason` | required, string, max:255 |
| `notes` | nullable, string, max:1000 |
| `staff` | required, array, min:1 |
| `staff.*.user_id` | required, exists:users,id |
| `staff.*.role` | required, enum:AppointmentRole |

Exactly one staff entry must have `role = Primary` — enforced via an `after` rule.

### `UpdateAppointmentRequest`
Same rules as `StoreAppointmentRequest`.

---

## Service: `AppointmentConflictService`

**Location:** `app/Services/AppointmentConflictService.php`

**Responsibility:** Given scheduling parameters and a list of user IDs, return which users already have overlapping appointments.

```php
public function findConflicts(
    string $date,
    string $start_time,
    string $end_time,
    array $userIds,
    ?int $excludeAppointmentId = null
): Collection  // Collection<User>
```

**Overlap condition:** An existing appointment on the same date overlaps if:
`existing.start_time < new.end_time AND existing.end_time > new.start_time`

When editing, pass the current appointment ID as `$excludeAppointmentId` so the appointment doesn't conflict with itself.

---

## Actions

### `BookAppointmentAction`

**Location:** `app/Actions/BookAppointmentAction.php`

**Steps:**
1. Call `AppointmentConflictService::findConflicts()` with the submitted staff user IDs.
2. If conflicts exist → throw `ValidationException` with a `staff` error listing conflicting staff names.
3. Create the `Appointment` record (with `patient_id`).
4. For each staff entry, call `$appointment->attachProvider($user, $role)`.

**Signature:**
```php
public function execute(Patient $patient, array $validated): Appointment
```

### `UpdateAppointmentAction`

**Location:** `app/Actions/UpdateAppointmentAction.php`

**Steps:**
1. Call `AppointmentConflictService::findConflicts()` passing `$appointment->id` as the exclusion.
2. If conflicts → throw `ValidationException` as above.
3. Update the `Appointment` record.
4. Sync staff: detach all existing users, then re-attach from the submitted staff array.

**Signature:**
```php
public function execute(Appointment $appointment, array $validated): Appointment
```

---

## Controller: `AppointmentController`

**Location:** `app/Http/Controllers/AppointmentController.php`

- `create(Patient $patient)` — renders `Appointments/Form` with patient, status options, role options, and staff list (all non-patient users).
- `store(StoreAppointmentRequest $request, Patient $patient)` — calls `BookAppointmentAction::execute()`, redirects to `patients.show`.
- `edit(Patient $patient, Appointment $appointment)` — renders `Appointments/Form` with existing appointment data.
- `update(UpdateAppointmentRequest $request, Patient $patient, Appointment $appointment)` — calls `UpdateAppointmentAction::execute()`, redirects to `patients.show`.

The controller injects `BookAppointmentAction` and `UpdateAppointmentAction` via constructor.

---

## Vue Pages

Following the established `Form.vue` + `Partials/AppointmentForm.vue` convention:

```
resources/js/Pages/Appointments/
  Form.vue                        ← page: create/edit mode, layout, back link
  Partials/
    AppointmentForm.vue           ← all form fields
```

### `Form.vue`
- `appointment` prop (null = create, object = edit)
- `patient` prop always present
- Derives `isEditing`, page title, `formAction` (route), `formMethod` from computed props
- Back link → `patients.show`

### `AppointmentForm.vue` fields
| Field | Input type |
|---|---|
| Date | DatePicker component |
| Start time | `<input type="time">` |
| End time | `<input type="time">` |
| Status | `<select>` from `status_options` prop |
| Reason | `<input type="text">` |
| Notes | `<textarea>` |
| Staff | Dynamic list: each row has a user select + role select (Primary/Assistant) |

**Staff picker:** Users can add multiple staff rows. At least one row required. Each row selects from the `staff_options` prop (list of non-patient users). Role defaults to Assistant; only one Primary allowed (enforced server-side, surfaced via `form.errors.staff`).

Conflict errors appear as a banner above the submit button, listing conflicting staff names from `form.errors.staff`.

---

## Error Handling

Conflict errors are thrown as `ValidationException` from the actions, which Inertia automatically surfaces as `form.errors`. The Vue form displays them inline.

---

## Tests

### `tests/Feature/AppointmentControllerTest.php`
- Happy path: create appointment → redirects to patient show
- Happy path: edit appointment → redirects to patient show
- Conflict on store → validation error returned, no appointment created
- Conflict on update → validation error returned, appointment unchanged
- Conflict check excludes the appointment being edited

### `tests/Unit/AppointmentConflictServiceTest.php`
- No overlap: adjacent appointments (end = next start)
- Overlap: partial overlap, full containment
- Exclude self: appointment correctly excluded when editing
- Multiple staff: only conflicting users returned
