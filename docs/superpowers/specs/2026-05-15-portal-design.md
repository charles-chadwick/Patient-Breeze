# Patient Portal — Design Spec

**Date:** 2026-05-15
**Status:** Approved

## Overview

A patient-facing portal at `/portal/` that is visually and structurally distinct from the clinical EHR. Patients log in with email + password and see a warm, friendly dashboard summarising their health information, appointments, messages, and documents.

---

## 1. Authentication

### Strategy
Make `Patient` directly authenticatable (Option A). The model already has an `email` field; we add password and token fields via migration. A dedicated `portal` guard keeps portal sessions fully isolated from staff sessions.

### Database changes
Migration adds to the `patients` table:
- `password` — `string`, nullable (nullable so existing records don't break)
- `remember_token` — `string(100)`, nullable
- `email_verified_at` — `timestamp`, nullable

### Model changes
`Patient` extends `Illuminate\Foundation\Auth\User as Authenticatable` instead of `Illuminate\Database\Eloquent\Model`. No other changes to the model's existing relationships or traits.

### Auth guard (`config/auth.php`)
```php
'guards' => [
    // existing...
    'portal' => ['driver' => 'session', 'provider' => 'patients'],
],
'providers' => [
    // existing...
    'patients' => ['driver' => 'eloquent', 'model' => App\Models\Patient::class],
],
```

### Password reset
Out of scope for this iteration.

---

## 2. Routes

All portal routes live under the `/portal` URL prefix and use the `portal` guard. They are defined in `routes/web.php` in a dedicated prefix group (no separate file — keeps routing in one place for now).

| Method | URI | Controller | Name | Middleware |
|--------|-----|------------|------|------------|
| GET | `/portal/login` | `Portal\LoginController@create` | `portal.login` | `guest:portal` |
| POST | `/portal/login` | `Portal\LoginController@store` | — | `guest:portal` |
| POST | `/portal/logout` | `Portal\LoginController@destroy` | `portal.logout` | — |
| GET | `/portal` | `Portal\DashboardController` | `portal.dashboard` | `auth:portal` |

Unauthenticated portal requests redirect to `portal.login`. Authenticated staff visiting `/portal/login` are not redirected (different guard).

---

## 3. Controllers

Located in `app/Http/Controllers/Portal/`.

**`Portal\LoginController`**
- `create()` — renders `Portal/Login` Inertia page
- `store()` — validates email + password, attempts login against the `portal` guard, redirects to `portal.dashboard` on success
- `destroy()` — logs out the portal guard, redirects to `portal.login`

**`Portal\DashboardController`** (single-action)
- Loads the authenticated patient with their upcoming appointments (next 5, ordered by date), recent discussions (last 3), and media (documents)
- Renders `Portal/Dashboard` Inertia page with those props

---

## 4. Frontend Layouts

### `PortalGuestLayout.vue`
Split-screen login wrapper:
- **Left panel** (`hidden lg:flex`, ~45% width): warm teal-to-sky gradient (`from-teal-500 to-sky-500`), practice name, a short welcoming tagline ("Your health, at your fingertips.")
- **Right panel** (full width on mobile, ~55% on desktop): white background, vertically centred login card

### `PortalLayout.vue`
Top-navigation authenticated wrapper:
- **Top bar**: white, `border-b border-slate-100`, `shadow-sm`. Practice name/logo left-aligned (`text-teal-600 font-bold`). Right side: patient full name + avatar initial circle + logout button.
- **Main content area**: `bg-slate-50 min-h-screen`, padded container, `max-w-5xl mx-auto`
- No sidebar.

---

## 5. Pages

### `Portal/Login.vue`
- Uses `PortalGuestLayout`
- Email + password fields matching the EHR login form's structure but with teal accent (`focus:ring-teal-500`, button `bg-teal-600 hover:bg-teal-700`)
- "Sign In to Your Portal" heading
- No "remember me" checkbox (keep it simple for v1)

### `Portal/Dashboard.vue`
Uses `PortalLayout`. Four sections:

**Health Summary** (full-width card, top)
- Patient's full name, MRN, date of birth, blood type, gender identity
- Warm welcome: "Welcome back, [First Name]"

**Two-column row**
- Left — **Upcoming Appointments**: next 5 appointments, each showing date, time, and provider name. Empty state: "No upcoming appointments."
- Right — **Messages**: last 3 discussions, showing subject and date. Empty state: "No messages."

**Documents** (full-width card, bottom)
- Lists media attached to the patient record (name, date uploaded)
- Empty state: "No documents on file."

---

## 6. Visual Design Tokens

Applied with Tailwind utility classes inline — no changes to global CSS or the existing theme.

| Element | Class |
|---------|-------|
| Primary accent | `teal-600` / `teal-500` |
| Page background | `bg-slate-50` |
| Cards | `bg-white rounded-2xl shadow-sm border border-slate-100` |
| Card headings | `text-base font-semibold text-slate-800` |
| Body text | `text-sm text-slate-600` |
| Muted text | `text-xs text-slate-400` |

---

## 7. Testing

- Feature test: `tests/Feature/Portal/LoginTest.php`
  - Patient can log in with correct credentials
  - Login fails with wrong password
  - Authenticated patient can reach the dashboard
  - Unauthenticated request to dashboard redirects to `portal.login`
- Feature test: `tests/Feature/Portal/DashboardTest.php`
  - Dashboard renders with health summary, appointments, messages, documents props

---

## 8. Out of Scope (v1)

- Password reset / forgot password flow
- Patient self-registration
- Document upload by patient
- Appointment booking from the portal
- Email verification
