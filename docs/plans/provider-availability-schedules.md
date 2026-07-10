# Provider Availability Schedules

## Context

Appointments can currently be booked for any date/time as long as the provider isn't double-booked (`AppointmentConflictService`). There is **no concept of when a provider is actually available to work.** This feature lets each staff user (provider) declare their working availability as a **recurring weekly pattern plus specific-date overrides**, and then **restricts appointment booking — on both the EHR/staff side and the patient portal side — to dates/times the provider is available.**

Decisions confirmed with the user:
- **Granularity:** date **and** time ranges (an appointment must land on an available date *and* fit inside an available time window).
- **Model:** recurring weekly pattern + specific-date overrides (add a one-off day / custom hours / day off).
- **Who edits:** each staff user manages their own schedule; users with an appointments-management permission can manage anyone's.

## Data model

Two new tables (idiomatic split: recurring rules vs. date exceptions).

**`availabilities`** — recurring weekly blocks (a provider may have several per day, e.g. 9–12 and 13–17):
`user_id` (FK→users, cascadeOnDelete), `day_of_week` (string, `DayOfWeek` enum), `start_time` (time), `end_time` (time), timestamps. Model `App\Models\Availability`.

**`availability_exceptions`** — specific-date overrides:
`user_id` (FK→users, cascadeOnDelete), `date` (date), `is_available` (bool), `start_time` (time, nullable), `end_time` (time, nullable), timestamps. Model `App\Models\AvailabilityException`.
- `is_available = false`, times null → **day off** (overrides the weekly pattern entirely).
- `is_available = true` with times → **custom hours for that date** (replaces the weekly windows for that date; also used to add availability on a normally-off day).

`User` gains `availabilities()` and `availabilityExceptions()` hasMany relations.

New enum **`App\Enums\DayOfWeek`** (string-backed, Title-Case values `Monday`…`Sunday`, `label()` via `__('enums.day_of_week.'.$value)`, plus `fromDate(CarbonInterface): self`). Mirrors `AppointmentRole` conventions.

Migrations: anonymous-class style with `foreignIdFor(User::class)->constrained()->cascadeOnDelete()`, `->comment()`, `timestamps()` (following `create_appointment_requests_table`). Factories `AvailabilityFactory`, `AvailabilityExceptionFactory`; a seeder gives seeded staff a default Mon–Fri 9–17 schedule so booking/tests work.

## Availability resolution — the shared brain

New **`App\Services\ProviderAvailabilityService`** (mirrors `AppointmentConflictService`):
- `resolveWindows(User $user, CarbonInterface $date): Collection` — returns the provider's available `[start,end]` windows for a date:
  1. If a full-day-off exception exists for the date → `[]` (unavailable).
  2. If `is_available=true` exceptions exist → use their windows (override weekly).
  3. Otherwise → the recurring `availabilities` for `DayOfWeek::fromDate($date)`.
- `isAvailable(User, string $date, string $start, string $end): bool` — true iff `[start,end]` fits entirely within one resolved window.
- `findUnavailable(string $date, string $start, string $end, array $user_ids): Collection<User>` — the enforcement helper used by the actions (returns providers who are NOT available).
- `availableDatesForRange(User, Carbon $from, Carbon $to): array` — preloads the user's rules+exceptions once, iterates days, returns `{ dates: string[], windows: { 'Y-m-d': [{start,end}] } }` for the frontend pickers.

## Enforcement (booking is blocked on unavailable slots)

Add a `findUnavailable(...)` check — alongside the existing conflict check — in all three booking entry points:
- `app/Actions/BookAppointmentAction.php` (EHR staff booking) → throw `ValidationException` on the `staff` key.
- `app/Actions/Portal/RequestAppointment.php` (portal request) → throw on `user_id`.
- `app/Actions/ApproveAppointmentRequest.php` (staff approving a portal request) → throw on `appointment_request`.

New i18n messages for the "provider not available at this time" errors (reuse the `portal.appointments.*` / `flash.appointment_requests.*` namespaces already used for conflict messages). Also add `after_or_equal:today`-style guardrails stay as-is.

## Schedule management UI (self + admins)

**Backend:** `AvailabilityController` (staff `auth` group) + `AvailabilityPolicy::manage($actor, $owner) => $actor->is($owner) || $actor->can('update_appointments')`. Owner is route-model-bound.
- `GET /availability` (optionally `?user=`) → `Inertia::render('Availability/Index', ...)` with the target user's weekly rules + exceptions, and — for managers — the list of staff to switch between (`User::staff()->forPicker()`).
- `PUT /availability/{user}` → replace the full weekly grid (delete-and-recreate in a transaction via a `SaveWeeklyAvailability` action). Body: `rules[] = {day_of_week, start_time, end_time}`.
- `POST /availability/{user}/exceptions` and `DELETE /availability/exceptions/{exception}` → add/remove a date override.
Form requests validate enum via `Rule::in(array_column(DayOfWeek::cases(),'value'))`, `end_time after start_time`, `date after_or_equal:today`.

**Frontend:** `resources/js/Pages/Availability/Index.vue` — a weekly grid (each `DayOfWeek` row with add/remove `TimePicker` blocks) and an exceptions list (add "day off" or "custom hours" for a date). Admins see a `StaffSelect`-style single-provider switcher at the top. Add a nav link in `DashboardLayout.vue`.

## Restrict pickers to available dates/times (both sides)

- **Extend `resources/js/Components/ui/DatePicker.vue`**: accept an optional `unavailableDates` (Set of `Y-m-d`) / `minValue` and pass reka-ui `DatePickerRoot`'s `:is-date-unavailable` + `:min-value`. Non-available dates render disabled (the component already styles `data-[disabled]`).
- **Availability endpoints** feeding the pickers:
  - Staff: `GET /appointments/providers/{user}/availability?from=&to=` (auth).
  - Portal: `GET /portal/appointment-requests/providers/{user}/availability` (portal.auth).
  Both return `availableDatesForRange(...)`.
- **`AppointmentForm.vue`**: when provider(s) are chosen in `StaffSelect`, fetch each one's availability, intersect available dates, and feed them to `DatePicker`; surface the day's windows near the `TimePicker`s. Multiple providers → intersection.
- **Portal `Dashboard.vue` request dialog**: when a provider is chosen in `ProviderSelect`, fetch availability and restrict its `DatePicker`.

Client-side restriction is UX; the server-side action checks are the real enforcement.

## Key files

- New: `app/Enums/DayOfWeek.php`, `app/Models/Availability.php`, `app/Models/AvailabilityException.php`, `app/Services/ProviderAvailabilityService.php`, `app/Actions/SaveWeeklyAvailability.php`, `app/Http/Controllers/AvailabilityController.php`, `app/Policies/AvailabilityPolicy.php`, form requests, 2 migrations, 2 factories, a seeder, `resources/js/Pages/Availability/Index.vue`.
- Modify: `app/Models/User.php` (relations), `app/Actions/BookAppointmentAction.php`, `app/Actions/Portal/RequestAppointment.php`, `app/Actions/ApproveAppointmentRequest.php`, `app/Http/Controllers/AppointmentController.php` + `Portal/AppointmentRequestController.php` (availability endpoints), `routes/web.php`, `resources/js/Components/ui/DatePicker.vue`, `resources/js/Pages/Appointments/Partials/AppointmentForm.vue`, `resources/js/Pages/Portal/Dashboard.vue`, `resources/js/Layouts/DashboardLayout.vue`, `lang/en/*.php`.

## Conventions honored

Enums string-backed Title-Case with `label()`/i18n; snake_case DB columns & PHP variables, camelCase methods; business logic in Actions/Services (not controllers); `DB::transaction`; form-request validation with `Rule::in`; policies auto-resolved; Inertia pages with `<script setup>` + `useForm` + `trans()`.

## Verification

- **Unit** (`ProviderAvailabilityService`): recurring window; custom-hours override; day-off; fit-within-window edge cases (exact bounds, overhang).
- **Feature:**
  - Availability CRUD — staff edits own; manager edits another; non-manager forbidden on another (assert 403 via policy).
  - `AvailabilityController` renders with correct props.
  - EHR `store` appointment **rejected** (422 on `staff`) when provider unavailable; **succeeds** when available.
  - Portal request **rejected** (`user_id`) when unavailable.
  - Approve request **rejected** (`appointment_request`) when unavailable.
  - Availability endpoints (staff + portal) return expected dates for a seeded schedule.
- Run: `php artisan test --compact` filtered to the new test files. `vendor/bin/pint --dirty --format agent` after PHP edits.
- **End-to-end (browser, claude-in-chrome):** set a provider's Mon–Fri 9–5 schedule + one day-off exception; confirm the EHR appointment DatePicker disables weekends and the day-off date; confirm the portal request dialog does the same for that provider; attempt an out-of-hours booking and confirm the server error.
