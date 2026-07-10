# Appointment Listing: Modal Edit Design

**Date:** 2026-07-09

## Summary

On the patient show page (`Patients/Show.vue`), the appointment records tab renders a
table of appointments. Today it includes a **Notes** column, and its **Edit** action
navigates to a separate full-page form (`patients.appointments.edit`).

This change:

1. Removes the **Notes** column from the listing table.
2. Keeps the Notes field in the edit form (it already exists there).
3. Makes the listing's **Edit** action open the form in a **modal** on the same page,
   instead of navigating away.

## Context

- The edit form lives in `resources/js/Pages/Appointments/Partials/AppointmentForm.vue`
  and already contains a Notes textarea. It is used for both create and edit on the
  full-page `Appointments/Form.vue`.
- The appointment payload rendered in the table comes from
  `Patient::paginatedAppointments()` (full Eloquent models with `users.media` loaded).
  It already includes `date`, `start_time`, `end_time`, `status`, `reason`, `notes`,
  and `users` (each with `pivot.role` and `avatar_url`) — everything the form needs.
- The app has an established modal-form convention to mirror:
  `MedicationsBlock` → `MedicationModal` → `MedicationForm`, built on the
  `resources/js/Components/ui/dialog` wrapper. The form exposes a `formId` prop and a
  `success` emit; the modal renders an external submit button in `DialogFooter` targeting
  that form via the `form` attribute.

## Approach

Follow the existing Medication modal pattern rather than introduce a new one.

### 1. `PatientController@show`

Pass the enum options the modal form needs:

- `status_options` — `array_column(AppointmentStatus::cases(), 'value')`
- `role_options` — `array_column(AppointmentRole::cases(), 'value')`

(Matches the shape `AppointmentController::sharedProps()` already produces for the
full-page form.)

### 2. `Patients/Show.vue`

- Remove the Notes column: the `<th>` (`patients.show.column_notes`) and the matching
  `<td>` (`appointment.notes`).
- Accept the new `status_options` / `role_options` props.
- Add a `selected_appointment` ref and a `modal_open` ref.
- Replace the Edit `<Link>` with a `<button>` that sets `selected_appointment` and opens
  the modal.
- Render one `<AppointmentModal>` bound to `selected_appointment`, `status_options`,
  `role_options`, and the patient id. On save, the modal closes; the update action's
  redirect back to `patients.show` refreshes the listing.

### 3. `AppointmentModal.vue` (new — `resources/js/Components/`)

- Wraps `Dialog` + `AppointmentForm`, edit-only (PUT to
  `patients.appointments.update`).
- Props: `open`, `patientId`, `appointment`, `status_options`, `role_options`.
- Emits `update:open` and `saved`.
- Title/description reuse existing `appointments.form.edit_title` and related keys.
- External Cancel + Submit buttons in `DialogFooter`; Submit targets the form via
  `form="appointment-form"`.
- Keyed on `appointment?.id` so the form re-seeds when a different row is edited.

### 4. `Appointments/Partials/AppointmentForm.vue`

Make it embeddable without changing full-page behavior:

- Add `formId` prop (default keeps current behavior; modal passes `appointment-form`)
  and set it as the `<form :id>`.
- Add a `showActions` prop (default `true`). When `false` (modal usage), hide the
  internal Cancel/Submit action bar — the modal supplies those buttons.
- Emit `success` from the submit `onSuccess` callback (add `preserveScroll: true`), so
  the modal can close on save. Full-page usage ignores the emit and still follows the
  server redirect as before.
- `cancelHref` becomes optional (only needed when `showActions` is true).

## Out of Scope (deliberately kept)

- The standalone edit page and route stay. Create still uses the full page, and the
  calendar `Appointments/Index.vue` popover's Edit link continues to work unchanged.
- No change to validation, actions, or the appointment payload shape.
- The `patients.show.column_notes` i18n key is left in place (harmless if unused).

## Testing

- **Feature test:** assert `Patients/Show` receives `status_options` and `role_options`.
- **Browser test (Pest v4):** open the appointments tab, click Edit on a row, confirm the
  modal opens with the Notes field populated, change it, submit, and confirm the value
  persists and the modal closes.
