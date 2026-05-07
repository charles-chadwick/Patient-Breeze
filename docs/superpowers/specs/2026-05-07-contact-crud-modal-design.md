# Contact CRUD Modal — Design Spec

**Date:** 2026-05-07  
**Scope:** Add a reusable contact form modal to the patient profile page, supporting full CRUD for one or more contacts per patient.

---

## Context

The `Contact` model exists with a polymorphic `contactable` relationship and fields `type`, `phone`, `street_address`. Contacts represent **other people** associated with a patient (emergency contacts, guardians, spouses, etc.) — not the patient's own contact info. The `ContactType` enum has: `Personal`, `Work`, `Emergency`, `Guardian`, `Spouse`, `Other`.

The `Patient` model already has a `contacts()` morphMany relationship. No controller or routes exist for contacts yet.

---

## Schema Change

Add a `name` (string, not nullable) column to the `contacts` table via a new migration. Update `Contact::$fillable` to include `name`.

---

## Backend

### Routes

```php
Route::resource('patients.contacts', ContactController::class)
    ->only(['store', 'update', 'destroy'])
    ->scoped();
```

Nested under the existing `auth` middleware group, following the same pattern as `patients.appointments`.

### ContactController

Three actions:

- **`store(StoreContactRequest $request, Patient $patient)`** — creates a contact via `$patient->contacts()->create(...)`, returns `redirect()->back()`.
- **`update(UpdateContactRequest $request, Patient $patient, Contact $contact)`** — resolves contact via `$patient->contacts()->findOrFail($contact->id)`, updates, returns `redirect()->back()`.
- **`destroy(Patient $patient, Contact $contact)`** — resolves contact via `$patient->contacts()->findOrFail($contact->id)`, deletes, returns `redirect()->back()`.

### Form Requests

Both `StoreContactRequest` and `UpdateContactRequest` validate:

| Field | Rules |
|---|---|
| `name` | required, string, max:255 |
| `type` | required, enum value of `ContactType` |
| `phone` | nullable, string, max:50 |
| `street_address` | nullable, string, max:255 |

### ContactResource

Exposes: `id`, `name`, `type`, `phone`, `street_address`.

### PatientController::show

Add `$patient->load(['media', 'contacts'])` and pass `'contacts' => ContactResource::collection($patient->contacts)` as an Inertia prop, enabling partial reloads targeting `only: ['contacts']`.

Also pass `'contact_type_options' => array_column(ContactType::cases(), 'value')` for the modal select.

---

## Frontend

### `ContactModal.vue` (`resources/js/Components/ui/ContactModal.vue`)

Reusable modal component:

- Uses `Teleport to="body"` and `v-if` (matches avatar modal pattern in `PatientCard.vue`)
- Props: `contact` (Object|null — null = create, object = edit), `action` (String URL), `method` (String: `'post'` or `'put'`), `contactTypeOptions` (Array)
- Uses `useForm` with fields: `name`, `type`, `phone`, `street_address`
- Watches `contact` prop to reset the form when switching between contacts
- Emits `close` on cancel or after successful submission
- Stays open on validation failure, surfaces errors inline per field
- Submit button disabled while `form.processing`

### `ContactsSection.vue` (`resources/js/Pages/Patients/Partials/ContactsSection.vue`)

Self-contained section card matching the Appointments card style:

- Header: "Contacts" label + "Add Contact" button (opens modal in create mode)
- Body: table with columns — Name, Type, Phone, Address, actions (Edit, Delete)
- Edit button opens `ContactModal` pre-populated with the selected contact
- Delete uses `router.delete(route('patients.contacts.destroy', [patient.id, contact.id]), { only: ['contacts'] })` with `window.confirm()` before proceeding
- Empty state: "No contacts on record." when list is empty
- Imports and renders `ContactModal`

### `Patients/Show.vue` updates

- Add `contacts` and `contactTypeOptions` to `defineProps`
- Import and render `<ContactsSection>` between `<PatientCard>` and the Appointments card
- Pass `patient`, `contacts`, and `contactTypeOptions` props to `ContactsSection`

---

## Data Flow

```
User clicks "Add Contact"
  → ContactModal opens (create mode)
  → useForm.post(route('patients.contacts.store', patient.id), { only: ['contacts'] })
  → ContactController::store creates contact, returns redirect()->back()
  → Inertia partial reload fetches only the 'contacts' prop from the redirected page
  → ContactsSection re-renders list
  → modal closes (emitted from form.post onSuccess callback)
```

Edit and delete follow the same partial reload pattern (`only: ['contacts']`).

---

## Security / Scoping

`ContactController` resolves contacts via `$patient->contacts()->findOrFail($contact->id)` — not bare `Contact::find()` — ensuring a patient can only access their own contacts. Laravel's route model binding handles the `Patient` resolution.

---

## Testing

Feature tests (`tests/Feature/ContactControllerTest.php`) covering:

- Store: valid data creates contact and redirects
- Store: invalid data returns validation errors (missing name, invalid type)
- Update: valid data updates the correct contact
- Update: cannot update a contact belonging to a different patient (404)
- Destroy: deletes the correct contact
- Destroy: cannot delete a contact belonging to a different patient (404)
- All actions require authentication

Uses `ContactFactory` (already exists) and `PatientFactory` for test setup.
