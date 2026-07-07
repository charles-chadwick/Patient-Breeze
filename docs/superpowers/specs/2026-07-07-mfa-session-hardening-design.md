# MFA & Session Hardening — Design

**Date:** 2026-07-07
**Status:** Approved (pending spec review)

## Goal

Add enforced multi-factor authentication (TOTP) and session hardening to the platform for both audiences — staff (`web` guard, `User` model) and patients (`portal` guard, `Patient` model) — to bring PHI access in line with standard healthcare security expectations.

## Scope

In scope:

- Enforced TOTP MFA for **both** guards (staff and patients).
- Recovery codes as the lockout escape hatch.
- Session hardening: idle timeout, re-authentication for sensitive actions, password policy.

Out of scope (explicitly deferred):

- Absolute/hard session cap (considered, not selected).
- SMS/email OTP, WebAuthn/passkeys, hardware keys.
- Per-organization MFA policy configuration.
- Password reset UX redesign (we harden the policy on existing paths only).

## Approach Decision

**Chosen:** Laravel Fortify for the staff guard; Fortify's underlying 2FA primitives for the patient guard.

**Key constraint:** Fortify authenticates a **single** guard (`config/fortify.php` → `guard`). It cannot natively serve both `web` and `portal`. Therefore:

- **Staff (`web`)** adopt Fortify's native machinery.
- **Patients (`portal`)** keep their existing custom login and gain a thin parallel 2FA flow built on the *same* Fortify primitives (`TwoFactorAuthenticatable` trait, `TwoFactorAuthenticationProvider`, recovery-code generation).

**Result:** one TOTP engine, two front doors. Staff get a battle-tested flow; patients get a small custom flow sharing the same crypto and storage shape.

**Dependency additions** (requires the repo's dependency-approval rule — approved during brainstorming):

- `laravel/fortify`
- `pragmarx/google2fa` and `bacon/bacon-qr-code` (transitive via Fortify)

## Architecture

### Guard responsibilities

| Concern | Staff (`web`) | Patient (`portal`) |
|---|---|---|
| Login handler | Fortify `AuthenticatedSessionController` | Existing custom `Portal\LoginController` |
| Login view | Existing `Auth/Login` Inertia page via `Fortify::loginView()` | Existing `Portal/Login` Inertia page |
| 2FA challenge | Fortify native (`/two-factor-challenge`) | Custom `Portal\TwoFactorChallengeController` |
| 2FA enable/confirm/disable | Fortify native routes | Custom `Portal\TwoFactorSetupController` |
| Recovery codes | Fortify native | Custom controller reusing Fortify's `RecoveryCode` + provider |
| Password confirmation | Fortify `password.confirm` | N/A for patients (re-auth is a staff concern) |

The staff custom `Auth\LoginController` is **replaced** by Fortify's pipeline. The Inertia login page is retained and wired through `Fortify::loginView()`. `Fortify::twoFactorChallengeView()` and the 2FA management views are registered to render new Inertia pages.

### Data model

Add Fortify's 2FA columns to **both** tables via migrations:

- `two_factor_secret` (text, nullable, encrypted by the trait)
- `two_factor_recovery_codes` (text, nullable, encrypted by the trait)
- `two_factor_confirmed_at` (timestamp, nullable)

Add the `Laravel\Fortify\TwoFactorAuthenticatable` trait to `App\Models\User` and `App\Models\Patient`.

Fortify's config uses `features` with `twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])` so enrollment requires confirming a valid TOTP before it is considered active, and viewing/regenerating 2FA settings requires password confirmation.

### Enforced enrollment

A per-guard middleware requires a confirmed second factor:

- `EnsureTwoFactorEnabled` (staff) applied to the `auth` route group.
- `Portal\EnsureTwoFactorEnabled` (patient) applied to the `portal.auth` route group.

Behavior: an authenticated user whose `two_factor_confirmed_at` is null is redirected to the mandatory setup screen and blocked from all other in-group routes until enrollment is confirmed. The setup route itself and logout are exempt to avoid a redirect loop.

Enrollment screen presents: QR code + manual secret, a confirm-code field, and — on success — the one-time display of recovery codes before proceeding.

### Session hardening

**Idle timeout** — `EnforceIdleTimeout` middleware stores `last_activity_at` in the session and logs the user out (with a "session expired" flash) when the inactivity window is exceeded. Sliding window: refreshed on each request. Durations are env-configurable:

- Staff: `SESSION_IDLE_TIMEOUT_STAFF` default 15 minutes.
- Patients: `SESSION_IDLE_TIMEOUT_PORTAL` default 30 minutes.

Applied per guard within the respective authenticated route groups. Distinct from `config/session.php` `lifetime` (cookie lifetime), which remains as the outer bound.

**Re-authentication for sensitive actions** — Laravel's `password.confirm` middleware (provided by Fortify) gates a concrete list of staff routes:

- User create / store
- User update
- 2FA disable and recovery-code regeneration

Force-delete has no HTTP route today; when one is added it joins this list. Patient-side re-auth is limited to disabling their own 2FA.

**Password policy** — a single shared rule object:

```
Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
```

Single source of truth: register this via `Password::defaults(...)` in `FortifyServiceProvider::boot()`, then reference `Password::defaults()` at every set/change entry point — staff creation (`StoreUserRequest`), patient creation, and Fortify's password-update/confirmation/reset paths (which honor `Password::defaults()` automatically). One definition, applied everywhere.

## Components

New/changed backend:

- `config/fortify.php`, `App\Providers\FortifyServiceProvider` — feature flags, guard = `web`, view + action bindings, `Password::defaults()`.
- Migrations: add 2FA columns to `users` and `patients`.
- `App\Models\User`, `App\Models\Patient` — add `TwoFactorAuthenticatable`.
- `App\Http\Middleware\EnsureTwoFactorEnabled` and `App\Http\Middleware\Portal\EnsureTwoFactorEnabled`.
- `App\Http\Middleware\EnforceIdleTimeout` (guard-parameterized).
- `App\Http\Controllers\Portal\TwoFactorSetupController`, `Portal\TwoFactorChallengeController` — patient enrollment + challenge on Fortify primitives.
- Removal/retirement of `App\Http\Controllers\Auth\LoginController` store/destroy in favor of Fortify routes (view retained via callback).
- Password policy application in `StoreUserRequest`, patient creation, and Fortify actions.

New frontend (Inertia/Vue):

- Staff: `Auth/TwoFactorChallenge.vue`, `Auth/TwoFactorSetup.vue`, `Auth/ConfirmPassword.vue`.
- Portal: `Portal/TwoFactorChallenge.vue`, `Portal/TwoFactorSetup.vue`.
- Recovery-codes display component (shared).

## Data Flow

### Staff login with enforced 2FA

1. `GET /login` → `Fortify::loginView` → `Auth/Login`.
2. `POST /login` → Fortify validates credentials.
   - If 2FA confirmed → Fortify redirects to `/two-factor-challenge`; user submits TOTP or recovery code; on success, session regenerates and redirects to intended/dashboard.
   - If credentials valid but 2FA not yet set up → login succeeds but `EnsureTwoFactorEnabled` redirects every request to the setup screen until confirmed.
3. After confirmation, recovery codes are shown once; user proceeds to dashboard.

### Patient login with enforced 2FA

Mirrors the above using the custom portal controllers and the shared TOTP provider. The custom `Portal\LoginController@store`, on valid credentials with 2FA confirmed, stashes the pending user id in the session and redirects to `Portal\TwoFactorChallengeController`; on valid TOTP/recovery code it completes the login. Un-enrolled patients are forced through `Portal\TwoFactorSetupController`.

### Idle timeout

Each authenticated request passes through `EnforceIdleTimeout`. If `now - last_activity_at > window`, log out + flash + redirect to the guard's login; otherwise update `last_activity_at`.

## Error Handling

- Invalid TOTP / recovery code → validation error on the challenge screen; no session state change; standard login throttling applies (`throttle` on challenge submission).
- Recovery code is single-use — consumed on success; reused codes fail.
- Idle-timeout logout is a clean session invalidation with a user-facing "session expired" message, not an error page.
- 2FA setup abandoned mid-flow → user remains un-enrolled and is re-prompted on next request (enforcement is idempotent).
- Password confirmation timeout re-prompts via `password.confirm` (Fortify default window).

## Testing Strategy

Pest feature tests, one behavior per test, both guards where applicable:

- Enrollment happy path: QR/secret issued, confirming a valid TOTP sets `two_factor_confirmed_at` and reveals recovery codes.
- Enforced redirect: an authenticated but un-enrolled user is redirected to setup and cannot reach other in-group routes; the setup route and logout are reachable.
- Challenge: correct TOTP completes login; incorrect TOTP is rejected and does not authenticate.
- Recovery code: logs in and is single-use (second use fails).
- Idle timeout: request after the window logs out; request within the window does not; window slides on activity.
- Password policy: weak/compromised passwords rejected at every set/change entry point; strong password accepted.
- Re-auth gating: a `password.confirm` route redirects to confirm-password when confirmation is stale and proceeds once confirmed.

TOTP is deterministic given a known secret, so tests generate valid codes from the stored secret via the Fortify/google2fa provider — no wall-clock dependence beyond the standard time window (freezable with Pest's time helpers).

## Risks & Notes

- **Single-guard Fortify** is the main architectural friction; mitigated by using Fortify natively for staff and its primitives (not its routes) for patients. The patient flow is deliberately thin.
- Replacing the staff `LoginController` with Fortify changes the staff auth entry point; the existing Inertia login view and existing login feature tests must be updated to the Fortify routes/redirects.
- Encrypted 2FA columns depend on `APP_KEY`; key rotation would invalidate stored secrets (operational note, not a code concern).
- Enforced enrollment applies to existing seeded users — after deploy, every current staff/patient account is forced through setup on next login. Acceptable and intended.
