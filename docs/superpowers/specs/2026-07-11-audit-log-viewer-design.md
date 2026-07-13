# Audit Log Viewer — Design

**Date:** 2026-07-11
**Status:** Approved for planning

## Summary

The app already records an audit trail: 11 models `use LogsActivity` (Spatie
activitylog v5), writing `event` (created/updated/deleted), `subject`, `causer`, and
`attribute_changes` to the `activity_log` table — including the sign/co-sign and the
soft-delete actions. Nothing surfaces it. This feature adds two read-only views over that
existing data:

1. A **per-patient History tab** on the patient chart (any staff who can view the patient).
2. A **global Audit Log page** under Administration (Super Admin only).

No new logging instrumentation beyond adding `LogsActivity` to `PatientMedication`
(currently unaudited) so medication changes appear in patient history.

## Linking activity to a patient (the core mechanism)

The per-patient History is "wide" — it includes activity on the patient's related records
(appointments, notes, meds, discussions, documents, contacts), not just the `Patient` row.
We denormalize a **`patient_id`** onto `activity_log` so the History query is a trivial,
indexed `where('patient_id', …)`.

Spatie v5 has **no `tapActivity()` model hook**, so `patient_id` is stamped by a global
listener registered in `AppServiceProvider`:

```php
Activity::creating(function (Activity $activity): void {
    $activity->patient_id = app(ResolvesActivityPatient::class)
        ->resolve($activity->subject_type, $activity->subject_id);
});
```

`ResolvesActivityPatient` loads the subject (`withTrashed()` so `deleted` events resolve)
and, if it implements the `LinksActivityToPatient` interface, returns `auditPatientId()`.
Each patient-related model implements that one method:

| Model | `auditPatientId()` |
|---|---|
| Patient | `$this->id` |
| Appointment, EncounterNote, PatientMedication | `$this->patient_id` |
| DiscussionPost | `$this->patient_id`, else the discussion's patient |
| Discussion, Note, Document, Contact | polymorphic parent → its id **iff** the parent is a `Patient` (Document also maps an `Appointment` parent → that appointment's `patient_id`) |
| User, DiscussionParticipant, Media | not implemented → `null` (global page only) |

**Media is intentionally excluded** from patient history to keep the timeline clinical.

A one-off `php artisan audit:backfill-patient-id` command repopulates existing rows using
the same resolver.

## Backend

- **Migration:** add nullable, indexed `patient_id` to `activity_log`.
- **`app/Contracts/LinksActivityToPatient.php`:** interface with `auditPatientId(): ?int`.
- **`app/Support/ResolvesActivityPatient.php`:** loads a subject `withTrashed()` and returns
  its `auditPatientId()` (or `null`). Reused by the listener and the backfill command.
- **`AppServiceProvider::boot()`:** registers the `Activity::creating` listener.
- **`PatientMedication`:** add `LogsActivity` + `getActivitylogOptions()` (logFillable, dirty).
- **`app/Support/ActivityPresenter.php`:** maps a raw `Activity` to a view array:
  `{ id, causer_name, action, subject_label, subject_type, subject_id, created_at, changes[] }`.
  `changes[]` is `{ field, old, new }` derived from `attribute_changes`. `subject_type` maps
  to friendly labels ("Encounter Note", "Demographics", …).

### Per-patient History
`PatientController@show` gains a **deferred, paginated** `history` prop
(`Activity::where('patient_id', $patient->id)->with('causer')->latest()->paginate()`,
presented through `ActivityPresenter`), matching how the chart already defers discussions
and paginates appointments. Pagination reloads only `history`.

### Global Audit Log
`GET /admin/audit-log` → `AuditLogController@index`, hard-gated to Super Admin (403
otherwise). Paginated, filterable by **causer, subject type, event, and date range**.
Renders `AuditLog/Index`. Sidebar link under Administration.

## Frontend

- **`Pages/Patients/Show.vue`:** new "History" tab in `primary_tabs`, rendering a timeline —
  causer · action · subject label · timestamp, each row expandable to show field-level
  **before → after**. Skeleton + empty states (deferred prop).
- **`Pages/AuditLog/Index.vue`:** filter bar (causer / subject type / event / date) + table
  (Causer · Action · Subject · When) with the same expandable detail rows + pagination
  footer, following the Users/Medications index conventions.
- **`DashboardLayout.vue`:** `{ nav.audit_log, 'audit-log.index', ScrollText }` in the
  Administration section (shown to all; page enforces Super Admin server-side).

## i18n

- `lang/en/audit.php`: action verbs (created/updated/deleted/…), subject-type labels,
  index/tab headings, column labels, filter labels, empty states.
- `lang/en/nav.php`: `audit_log`.

## Authorization

- History tab: no new permission — piggybacks the existing `view` patient authorization in
  `PatientController@show`.
- Audit page: Super-Admin role check in the controller (mirrors the `forceDelete` convention).

## Testing

- `ResolvesActivityPatient` / `auditPatientId()` stamp the right `patient_id` per model
  (update a patient, sign a note, edit a patient medication, patient-scoped
  discussion/document/contact); unrelated activity (a User edit) stamps `null`.
- The `Activity::creating` listener sets `patient_id` end-to-end on a real save.
- `audit:backfill-patient-id` populates pre-existing rows.
- History prop is scoped to one patient and presented.
- Audit page: Super Admin 200; every other role 403; each filter narrows correctly.

## Out of scope

- Editing/deleting audit entries (immutable), retention/purging, CSV export.
- Media activity in the patient timeline.
