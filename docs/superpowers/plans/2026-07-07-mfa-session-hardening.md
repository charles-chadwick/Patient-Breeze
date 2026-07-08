# MFA & Session Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add enforced TOTP multi-factor authentication and session hardening (idle timeout, re-auth for sensitive actions, password policy) to both the staff (`web`) and patient (`portal`) guards.

**Architecture:** Laravel Fortify drives 2FA for the staff `web` guard natively (login, challenge, enable/confirm/disable, recovery codes, password confirmation), rendering the existing Inertia pages via Fortify's view callbacks. Fortify authenticates only one guard, so the patient `portal` guard keeps its custom Inertia login and gains a thin parallel 2FA flow built on Fortify's shared primitives (`TwoFactorAuthenticatable` trait, `TwoFactorAuthenticationProvider`, `RecoveryCode`). One TOTP engine, two front doors.

**Tech Stack:** Laravel 13, Inertia v3 + Vue 3, Pest 4, `laravel/fortify` (new), `pragmarx/google2fa` + `bacon/bacon-qr-code` (transitive via Fortify), `spatie/laravel-permission` (existing).

## Global Constraints

- PHP `^8.3`; Laravel `^13.0`. Follow existing conventions.
- **Naming (project global):** variables `snake_case`; methods/functions `camelCase`; classes `TitleCase`. Explicit return types and param type hints on all methods. Curly braces on all control structures. Descriptive closure params (`fn ($query) => ...`, never `fn ($q)`).
- Dependency additions already approved in the spec: `laravel/fortify` only (plus its transitive deps). Do not add others.
- Every change is programmatically tested (Pest). Run `php artisan test --compact` with a filter. New tests are feature tests unless stated.
- Run `vendor/bin/pint --dirty --format agent` before every commit.
- Two guards exist: `web` (`App\Models\User`, staff) and `portal` (`App\Models\Patient`, patients). Guard config in `config/auth.php`.
- Idle timeouts (env-configurable): staff 15 min (`SESSION_IDLE_TIMEOUT_STAFF=15`), patients 30 min (`SESSION_IDLE_TIMEOUT_PORTAL=30`).
- Password policy: `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()`, defined once via `Password::defaults()`.
- Re-auth (`password.confirm`) routes: user create/store, user update, 2FA disable, 2FA recovery-code regenerate.
- Enforced enrollment applies to all existing accounts on next login (intended).
- Work happens on branch `security/authz-and-mfa-foundation` (already checked out).

---

## File Structure

Backend:
- `config/fortify.php` (published) — Fortify config; `guard = web`, feature flags.
- `app/Providers/FortifyServiceProvider.php` (published, edited) — view callbacks, `Password::defaults()`, action bindings.
- `bootstrap/providers.php` (edit) — register `FortifyServiceProvider`.
- `bootstrap/app.php` (edit) — register middleware aliases `two-factor.required`, `portal.two-factor.required`, `idle-timeout`.
- `database/migrations/*_add_two_factor_columns_to_users_table.php` (published by Fortify).
- `database/migrations/*_add_two_factor_columns_to_patients_table.php` (new) — same columns for patients.
- `app/Models/User.php`, `app/Models/Patient.php` (edit) — add `TwoFactorAuthenticatable`.
- `app/Rules/` — not needed; policy lives in `Password::defaults()`.
- `app/Http/Middleware/EnsureTwoFactorEnabled.php` (new) — staff enforced enrollment.
- `app/Http/Middleware/Portal/EnsureTwoFactorEnabled.php` (new) — patient enforced enrollment.
- `app/Http/Middleware/EnforceIdleTimeout.php` (new) — guard-parameterized idle logout.
- `app/Http/Controllers/Portal/TwoFactorSetupController.php` (new) — patient enrollment.
- `app/Http/Controllers/Portal/TwoFactorChallengeController.php` (new) — patient challenge at login.
- `app/Http/Controllers/Portal/LoginController.php` (edit) — branch to challenge when 2FA confirmed.
- `app/Http/Controllers/Auth/LoginController.php` (delete `store`/`destroy`; Fortify owns them. Keep file only if `create` still referenced — see Task 2).
- `app/Http/Requests/StoreUserRequest.php` (edit) — apply `Password::defaults()`.
- Patient creation password rule — locate in `PatientSeeder`/any patient store path (Task 8).
- `routes/web.php` (edit) — apply middleware groups, add password-confirm route group, patient 2FA routes.

Frontend (Inertia/Vue), matching `resources/js/Pages/Auth/Login.vue` conventions (`useForm`, `route()`, `GuestLayout`, `setLayoutProps`):
- `resources/js/Pages/Auth/TwoFactorChallenge.vue` (new).
- `resources/js/Pages/Auth/TwoFactorSetup.vue` (new).
- `resources/js/Pages/Auth/ConfirmPassword.vue` (new).
- `resources/js/Pages/Portal/TwoFactorChallenge.vue` (new).
- `resources/js/Pages/Portal/TwoFactorSetup.vue` (new).

Tests:
- `tests/Feature/Auth/TwoFactorEnrollmentTest.php`
- `tests/Feature/Auth/TwoFactorChallengeTest.php`
- `tests/Feature/Auth/PasswordConfirmationTest.php`
- `tests/Feature/Portal/TwoFactorTest.php`
- `tests/Feature/IdleTimeoutTest.php`
- `tests/Feature/PasswordPolicyTest.php`
- `tests/Feature/Auth/LoginTest.php` (edit — Fortify routes)

---

## Task 1: Install & configure Fortify for the staff guard

Establishes Fortify, points its login view at the existing Inertia page, disables features we don't want, and confirms staff login still works through Fortify's pipeline.

**Files:**
- Modify: `composer.json` (via composer require)
- Create/publish: `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `database/migrations/*_add_two_factor_columns_to_users_table.php`
- Modify: `bootstrap/providers.php`
- Modify: `routes/web.php` (remove staff login/logout routes now owned by Fortify)
- Modify: `app/Http/Controllers/Auth/LoginController.php` (remove `store`/`destroy`)
- Test: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Produces: Fortify routes on the `web` guard. Exact route names are confirmed in Step 4 and reused by later tasks: login (`login`), logout (`logout`), two-factor challenge (`two-factor.login`), enable/confirm/disable + recovery/qr routes, and password confirmation (`password.confirm`).
- Produces: `App\Providers\FortifyServiceProvider` with `Password::defaults()` (consumed by Task 8) and view callbacks (consumed by Tasks 4, 7).

- [ ] **Step 1: Install Fortify**

Run:
```bash
composer require laravel/fortify
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"
```
Expected: `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, and a `*_add_two_factor_columns_to_users_table.php` migration are created.

- [ ] **Step 2: Register the provider**

In `bootstrap/providers.php`, add `App\Providers\FortifyServiceProvider::class` to the returned array (alongside `AppServiceProvider`).

- [ ] **Step 3: Configure `config/fortify.php`**

Set the guard and features. Replace the `guard`, `home`, and `features` values:
```php
'guard' => 'web',

'home' => '/', // dashboard route path

'features' => [
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```
Remove `Features::registration()`, `Features::resetPasswords()`, `Features::emailVerification()`, and `Features::updateProfileInformation()` from the `features` array — this app manages users via its own controllers and has no self-registration.

- [ ] **Step 4: Wire view callbacks and confirm routes**

In `app/Providers/FortifyServiceProvider.php` `boot()`, register Inertia views and keep the existing login page:
```php
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

Fortify::loginView(fn () => Inertia::render('Auth/Login'));
Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));
Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));
```
Then run and record the real route names (later tasks depend on them):
```bash
php artisan route:list --name=two-factor
php artisan route:list --name=password
php artisan route:list --name=login
```
Expected: a `login` (GET+POST), `logout` (POST), `two-factor.login` (GET+POST), `two-factor.enable`/`two-factor.confirm`/`two-factor.disable`/`two-factor.qr-code`/`two-factor.recovery-codes`, and `password.confirm` (GET) + confirmation POST. **Record these exact names in a comment at the top of `tests/Feature/Auth/LoginTest.php`.**

- [ ] **Step 5: Remove the now-duplicated custom staff auth routes**

In `routes/web.php`, delete the `guest`-group `login` GET/POST routes and the `logout` POST route (Fortify now registers them). Keep the `home` redirect. Delete the `store()` and `destroy()` methods from `app/Http/Controllers/Auth/LoginController.php` (Fortify owns them); the `create()` method is also now unused because `Fortify::loginView` renders the page — delete the file and remove its import from `routes/web.php`.

- [ ] **Step 6: Run the migration**

Run:
```bash
php artisan migrate
```
Expected: two-factor columns added to `users`.

- [ ] **Step 7: Update the login feature test to Fortify routes**

Existing `tests/Feature/Auth/LoginTest.php` posts to `route('login')`. Confirm the assertions still hold under Fortify (valid credentials → redirect to intended/home; invalid → back with `email` error). Update any route-name or redirect-path assumptions to the names recorded in Step 4. Run:
```bash
php artisan test --compact tests/Feature/Auth/LoginTest.php
```
Expected: PASS.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: install and configure Fortify for the staff guard"
```

---

## Task 2: Add two-factor storage to patients and the trait to both models

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_two_factor_columns_to_patients_table.php`
- Modify: `app/Models/User.php`, `app/Models/Patient.php`
- Test: `tests/Feature/Auth/TwoFactorEnrollmentTest.php` (first assertion only)

**Interfaces:**
- Produces: `User` and `Patient` both use `Laravel\Fortify\TwoFactorAuthenticatable`, exposing `->two_factor_secret`, `->two_factor_recovery_codes`, `->two_factor_confirmed_at`, and trait methods used by later tasks.

- [ ] **Step 1: Create the patients migration**

Run:
```bash
php artisan make:migration add_two_factor_columns_to_patients_table --table=patients
```
Fill it to mirror the users migration Fortify published:
```php
public function up(): void
{
    Schema::table('patients', function (Blueprint $table): void {
        $table->text('two_factor_secret')->after('password')->nullable();
        $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
        $table->timestamp('two_factor_confirmed_at')->after('two_factor_recovery_codes')->nullable();
    });
}

public function down(): void
{
    Schema::table('patients', function (Blueprint $table): void {
        $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
    });
}
```

- [ ] **Step 2: Add the trait to both models**

In `app/Models/User.php` add `use Laravel\Fortify\TwoFactorAuthenticatable;` and include `TwoFactorAuthenticatable` in the `use` trait list. Do the same in `app/Models/Patient.php`.

- [ ] **Step 3: Migrate**

Run:
```bash
php artisan migrate
```
Expected: patients table gains the three columns.

- [ ] **Step 4: Write a smoke test proving enrollment storage works**

Create `tests/Feature/Auth/TwoFactorEnrollmentTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

it('stores and verifies a two-factor secret for a user', function (): void {
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();

    $user = User::factory()->withRole(UserRole::Doctor)->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $code = $provider->getCurrentOtp($secret);

    expect($provider->verify(decrypt($user->two_factor_secret), $code))->toBeTrue();
});
```

- [ ] **Step 5: Run it**

Run:
```bash
php artisan test --compact tests/Feature/Auth/TwoFactorEnrollmentTest.php
```
Expected: PASS. (If `getCurrentOtp` is unavailable in the installed `pragmarx/google2fa`, generate the code via `new PragmaRX\Google2FA\Google2FA()->getCurrentOtp($secret)` — confirm the available method with `php artisan tinker`.)

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add two-factor storage to patients and trait to both models"
```

---

## Task 3: Enforce staff 2FA enrollment

Force any logged-in staff user without a confirmed second factor to a setup screen, blocking all other routes.

**Files:**
- Create: `app/Http/Middleware/EnsureTwoFactorEnabled.php`
- Modify: `bootstrap/app.php` (alias `two-factor.required`)
- Modify: `routes/web.php` (apply to the `auth` group; exempt setup + logout)
- Create: `resources/js/Pages/Auth/TwoFactorSetup.vue`
- Test: `tests/Feature/Auth/TwoFactorEnrollmentTest.php`

**Interfaces:**
- Consumes: `two_factor_confirmed_at` from Task 2.
- Produces: middleware alias `two-factor.required`; a route named `two-factor.setup` (GET) rendering `Auth/TwoFactorSetup`.

- [ ] **Step 1: Write the failing enforcement test**

Add to `tests/Feature/Auth/TwoFactorEnrollmentTest.php`:
```php
use function Pest\Laravel\actingAs;

it('redirects an enrolled-pending staff user to setup and blocks other routes', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create([
        'two_factor_confirmed_at' => null,
    ]);

    actingAs($user)->get(route('patients.index'))
        ->assertRedirect(route('two-factor.setup'));
});

it('allows a confirmed staff user through', function (): void {
    $user = User::factory()->withRole(UserRole::Doctor)->create([
        'two_factor_secret' => encrypt(app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);

    actingAs($user)->get(route('patients.index'))->assertSuccessful();
});
```

- [ ] **Step 2: Run to verify failure**

Run:
```bash
php artisan test --compact --filter="redirects an enrolled-pending staff user"
```
Expected: FAIL (`route('two-factor.setup')` undefined / no redirect).

- [ ] **Step 3: Create the middleware**

`app/Http/Middleware/EnsureTwoFactorEnabled.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->two_factor_confirmed_at === null) {
            return redirect()->route('two-factor.setup');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the alias and setup route**

In `bootstrap/app.php` `withMiddleware`, add to the `alias` array:
```php
'two-factor.required' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
```
In `routes/web.php`, add a setup route reachable while pending, then apply the middleware to the authenticated group. The setup route and logout must be OUTSIDE the `two-factor.required` middleware to avoid a redirect loop:
```php
Route::middleware('auth')->group(function () {
    Route::get('/two-factor-setup', fn () => Inertia::render('Auth/TwoFactorSetup'))->name('two-factor.setup');

    Route::middleware('two-factor.required')->group(function () {
        // ... existing dashboard, patients, appointments, users, contacts,
        // discussions, portal-queue routes move inside here ...
    });
});
```
(Move the existing `auth`-group routes inside the inner `two-factor.required` group. `logout` is a Fortify route and is unaffected.)

- [ ] **Step 5: Create the setup Vue page**

`resources/js/Pages/Auth/TwoFactorSetup.vue` — fetch the QR + secret from Fortify's `two-factor.qr-code` endpoint after enabling, confirm a code via `two-factor.confirm`, then show recovery codes. Minimal working version following `Login.vue` conventions:
```vue
<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { setLayoutProps } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })
setLayoutProps({ title: 'Set Up Two-Factor Authentication' })

const qr_svg = ref('')
const recovery_codes = ref([])
const confirm_form = useForm({ code: '' })

async function enable() {
    await window.axios?.post(route('two-factor.enable'))
    const qr = await window.fetch(route('two-factor.qr-code')).then((response) => response.json())
    qr_svg.value = qr.svg
}

function confirm() {
    confirm_form.post(route('two-factor.confirm'), {
        onSuccess: async () => {
            recovery_codes.value = await window.fetch(route('two-factor.recovery-codes')).then((response) => response.json())
        },
    })
}

function finish() {
    router.visit(route('dashboard'))
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white p-8 shadow-sm">
        <h1 class="mb-4 text-xl font-bold text-foreground">Set Up Two-Factor Authentication</h1>
        <button v-if="!qr_svg" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white" @click="enable">Begin setup</button>
        <div v-else class="grid gap-4">
            <div v-html="qr_svg" />
            <form class="grid gap-2" @submit.prevent="confirm">
                <input v-model="confirm_form.code" inputmode="numeric" autocomplete="one-time-code" class="rounded-lg border border-border px-3 py-2 text-sm" placeholder="123456" />
                <p v-if="confirm_form.errors.code" class="text-xs text-vibrant-coral-600">{{ confirm_form.errors.code }}</p>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white">Confirm</button>
            </form>
            <div v-if="recovery_codes.length" class="grid gap-2">
                <p class="text-sm font-bold">Save your recovery codes</p>
                <ul class="rounded-lg bg-muted p-3 text-xs"><li v-for="code in recovery_codes" :key="code">{{ code }}</li></ul>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white" @click="finish">Continue</button>
            </div>
        </div>
    </div>
</template>
```
(Note: Inertia v3 removed Axios; use the built-in XHR client or `window.fetch` as shown. Verify the endpoints return the expected shapes in Step 6; adjust the fetch/JSON handling to the actual Fortify response contract recorded in Task 1 Step 4.)

- [ ] **Step 6: Run the tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/TwoFactorEnrollmentTest.php
```
Expected: PASS (redirect-to-setup and confirmed-passes both green).

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: enforce staff two-factor enrollment"
```

---

## Task 4: Staff 2FA challenge at login

Fortify handles the challenge redirect automatically once a user has `two_factor_confirmed_at`. This task adds the challenge Vue page and a test proving the flow.

**Files:**
- Create: `resources/js/Pages/Auth/TwoFactorChallenge.vue`
- Test: `tests/Feature/Auth/TwoFactorChallengeTest.php`

**Interfaces:**
- Consumes: Fortify `two-factor.login` routes (Task 1), TOTP provider (Task 2).

- [ ] **Step 1: Write the failing challenge test**

`tests/Feature/Auth/TwoFactorChallengeTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

function enrolledStaff(string &$secret): User
{
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();

    return User::factory()->withRole(UserRole::Doctor)->create([
        'password' => bcrypt('correct-horse-battery-staple-12'),
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);
}

it('requires a second factor after valid credentials', function (): void {
    $secret = '';
    $user = enrolledStaff($secret);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple-12',
    ])->assertRedirect(route('two-factor.login'));

    expect(auth()->check())->toBeFalse();
});

it('completes login with a valid TOTP code', function (): void {
    $secret = '';
    $user = enrolledStaff($secret);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple-12',
    ]);

    $code = app(TwoFactorAuthenticationProvider::class)->getCurrentOtp($secret);

    $this->post(route('two-factor.login'), ['code' => $code])
        ->assertRedirect();

    expect(auth()->check())->toBeTrue();
});

it('rejects an invalid TOTP code', function (): void {
    $secret = '';
    $user = enrolledStaff($secret);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple-12',
    ]);

    $this->post(route('two-factor.login'), ['code' => '000000'])
        ->assertSessionHasErrors();

    expect(auth()->check())->toBeFalse();
});
```

- [ ] **Step 2: Run to verify current behavior**

Run:
```bash
php artisan test --compact tests/Feature/Auth/TwoFactorChallengeTest.php
```
Expected: the redirect/complete/reject behavior is largely provided by Fortify. Fix route names to those recorded in Task 1 Step 4 if any assertion fails on a name mismatch.

- [ ] **Step 3: Create the challenge Vue page**

`resources/js/Pages/Auth/TwoFactorChallenge.vue`:
```vue
<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { setLayoutProps } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })
setLayoutProps({ title: 'Two-Factor Challenge' })

const use_recovery = ref(false)
const form = useForm({ code: '', recovery_code: '' })

function submit() {
    form.post(route('two-factor.login'), { onFinish: () => form.reset('code', 'recovery_code') })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white p-8 shadow-sm">
        <h1 class="mb-6 text-xl font-bold text-foreground">Two-Factor Challenge</h1>
        <form class="grid gap-5" @submit.prevent="submit">
            <div v-if="!use_recovery">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">Authentication code</label>
                <input v-model="form.code" inputmode="numeric" autocomplete="one-time-code" autofocus class="w-full rounded-lg border border-border px-3 py-2 text-sm" placeholder="123456" />
                <p v-if="form.errors.code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.code }}</p>
            </div>
            <div v-else>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-muted-foreground">Recovery code</label>
                <input v-model="form.recovery_code" autocomplete="one-time-code" class="w-full rounded-lg border border-border px-3 py-2 text-sm" />
                <p v-if="form.errors.recovery_code" class="mt-1 text-xs text-vibrant-coral-600">{{ form.errors.recovery_code }}</p>
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Verify</button>
            <button type="button" class="text-xs text-muted-foreground underline" @click="use_recovery = !use_recovery">{{ use_recovery ? 'Use authentication code' : 'Use a recovery code' }}</button>
        </form>
    </div>
</template>
```

- [ ] **Step 4: Add a recovery-code test**

Append to `tests/Feature/Auth/TwoFactorChallengeTest.php`:
```php
it('completes login with a single-use recovery code', function (): void {
    $secret = '';
    $user = enrolledStaff($secret);
    $user->forceFill([
        'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-0001', 'test-recovery-0002'])),
    ])->save();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple-12',
    ]);

    $this->post(route('two-factor.login'), ['recovery_code' => 'test-recovery-0001'])->assertRedirect();
    expect(auth()->check())->toBeTrue();

    auth()->logout();

    // Reused code must fail.
    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple-12',
    ]);
    $this->post(route('two-factor.login'), ['recovery_code' => 'test-recovery-0001'])->assertSessionHasErrors();
});
```

- [ ] **Step 5: Run all challenge tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/TwoFactorChallengeTest.php
```
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: staff two-factor challenge screen and flow"
```

---

## Task 5: Idle timeout middleware (both guards)

**Files:**
- Create: `app/Http/Middleware/EnforceIdleTimeout.php`
- Modify: `bootstrap/app.php` (alias `idle-timeout`)
- Modify: `routes/web.php` (apply `idle-timeout:web,SESSION_IDLE_TIMEOUT_STAFF` to staff group; `idle-timeout:portal,SESSION_IDLE_TIMEOUT_PORTAL` to portal group)
- Modify: `.env.example` (document the two vars)
- Test: `tests/Feature/IdleTimeoutTest.php`

**Interfaces:**
- Produces: middleware alias `idle-timeout` accepting `{guard},{minutes}` parameters, e.g. `idle-timeout:web,15`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/IdleTimeoutTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

use function Pest\Laravel\actingAs;

function confirmedStaff(): User
{
    return User::factory()->withRole(UserRole::Doctor)->create([
        'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);
}

it('logs out a staff user after the idle window elapses', function (): void {
    $user = confirmedStaff();

    actingAs($user)->withSession(['last_activity_at' => now()->subMinutes(20)->timestamp])
        ->get(route('patients.index'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('keeps a staff user within the idle window', function (): void {
    $user = confirmedStaff();

    actingAs($user)->withSession(['last_activity_at' => now()->subMinutes(5)->timestamp])
        ->get(route('patients.index'))
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run to verify failure**

Run:
```bash
php artisan test --compact tests/Feature/IdleTimeoutTest.php
```
Expected: FAIL (no logout on idle).

- [ ] **Step 3: Create the middleware**

`app/Http/Middleware/EnforceIdleTimeout.php`:
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next, string $guard, string $timeoutMinutes): Response
    {
        $minutes = (int) $timeoutMinutes;
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity !== null && (now()->timestamp - (int) $lastActivity) > $minutes * 60) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $loginRoute = $guard === 'portal' ? 'portal.login' : 'login';

            return redirect()->route($loginRoute)->with('status', 'Your session expired due to inactivity.');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the alias and apply it**

In `bootstrap/app.php` alias array:
```php
'idle-timeout' => \App\Http\Middleware\EnforceIdleTimeout::class,
```
In `routes/web.php`, apply to the staff group (inside `auth`, alongside `two-factor.required`) using the env default:
```php
Route::middleware(['two-factor.required', 'idle-timeout:web,'.env('SESSION_IDLE_TIMEOUT_STAFF', 15)])->group(function () {
    // staff routes
});
```
And to the portal authenticated group:
```php
Route::middleware(['portal.auth', 'idle-timeout:portal,'.env('SESSION_IDLE_TIMEOUT_PORTAL', 30)])->group(function () {
    // portal routes
});
```

- [ ] **Step 5: Document env vars**

Add to `.env.example`:
```
SESSION_IDLE_TIMEOUT_STAFF=15
SESSION_IDLE_TIMEOUT_PORTAL=30
```

- [ ] **Step 6: Run tests**

Run:
```bash
php artisan test --compact tests/Feature/IdleTimeoutTest.php
```
Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: idle-timeout middleware for both guards"
```

---

## Task 6: Password policy

**Files:**
- Modify: `app/Providers/FortifyServiceProvider.php` (`Password::defaults()` in `boot()`)
- Modify: `app/Http/Requests/StoreUserRequest.php` (use `Password::defaults()`)
- Test: `tests/Feature/PasswordPolicyTest.php`

**Interfaces:**
- Consumes: `FortifyServiceProvider::boot()` (Task 1).
- Produces: `Password::defaults()` returns the project policy; applied at every set/change point.

- [ ] **Step 1: Write the failing test**

`tests/Feature/PasswordPolicyTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;

use function Pest\Laravel\actingAs;

function superAdmin(): User
{
    return User::factory()->withRole(UserRole::SuperAdmin)->create([
        'two_factor_secret' => encrypt(app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);
}

it('rejects a weak password when creating a user', function (): void {
    actingAs(superAdmin())->post(route('users.store'), [
        'first_name' => 'New',
        'last_name' => 'Hire',
        'email' => 'new.hire@example.com',
        'role' => UserRole::Nurse->value,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');
});

it('accepts a strong password when creating a user', function (): void {
    actingAs(superAdmin())->post(route('users.store'), [
        'first_name' => 'New',
        'last_name' => 'Hire',
        'email' => 'new.hire@example.com',
        'role' => UserRole::Nurse->value,
        'password' => 'Str0ng!Passphrase20',
        'password_confirmation' => 'Str0ng!Passphrase20',
    ])->assertRedirect();
});
```
(Adjust the `users.store` payload keys to `StoreUserRequest`'s actual fields — read it first. Note `password.confirm` re-auth from Task 7 is not yet applied, so this test passes on its own; after Task 7, add `->withSession(['auth.password_confirmed_at' => now()->timestamp])`.)

- [ ] **Step 2: Run to verify failure**

Run:
```bash
php artisan test --compact tests/Feature/PasswordPolicyTest.php
```
Expected: the "rejects a weak password" test FAILS (weak password currently accepted).

- [ ] **Step 3: Define the default policy**

In `app/Providers/FortifyServiceProvider.php` `boot()`:
```php
use Illuminate\Validation\Rules\Password;

Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised());
```
> Note: `uncompromised()` calls the HaveIBeenPwned API. In tests it may hit the network; if the suite must stay offline, wrap with `$this->app->isProduction()` like the Laravel docs example, or fake the HTTP client. Keep `uncompromised()` active in production.

- [ ] **Step 4: Apply it in `StoreUserRequest`**

In `app/Http/Requests/StoreUserRequest.php` `rules()`, set the password rule to:
```php
'password' => ['required', 'confirmed', Password::defaults()],
```
(Import `Illuminate\Validation\Rules\Password`.)

- [ ] **Step 5: Run tests**

Run:
```bash
php artisan test --compact tests/Feature/PasswordPolicyTest.php
```
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: enforce a strong default password policy"
```

---

## Task 7: Re-auth for sensitive staff actions

**Files:**
- Create: `resources/js/Pages/Auth/ConfirmPassword.vue`
- Modify: `routes/web.php` (apply `password.confirm` to user create/store/update + 2FA disable/regenerate)
- Test: `tests/Feature/Auth/PasswordConfirmationTest.php`

**Interfaces:**
- Consumes: Fortify `password.confirm` route + confirmation POST (Task 1), `confirmPasswordView` (Task 1 Step 4).

- [ ] **Step 1: Write the failing test**

`tests/Feature/Auth/PasswordConfirmationTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;

use function Pest\Laravel\actingAs;

function confirmedSuperAdmin(): User
{
    return User::factory()->withRole(UserRole::SuperAdmin)->create([
        'two_factor_secret' => encrypt(app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);
}

it('redirects to password confirmation before reaching user creation', function (): void {
    actingAs(confirmedSuperAdmin())->get(route('users.create'))
        ->assertRedirect(route('password.confirm'));
});

it('allows user creation once the password is freshly confirmed', function (): void {
    actingAs(confirmedSuperAdmin())
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('users.create'))
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run to verify failure**

Run:
```bash
php artisan test --compact tests/Feature/Auth/PasswordConfirmationTest.php
```
Expected: FAIL (`users.create` reachable without confirmation).

- [ ] **Step 3: Apply `password.confirm` to sensitive routes**

In `routes/web.php`, wrap the sensitive user-management routes and the Fortify 2FA disable/regenerate routes with `password.confirm`. Since the user resource is registered with `Route::resource`, split out the guarded verbs:
```php
Route::middleware('password.confirm')->group(function () {
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
});
```
Remove those four verbs from the existing `Route::resource('users', ...)->only([...])` (leave `index`, `show`). Keep them inside the `two-factor.required` + `idle-timeout` group.

- [ ] **Step 4: Create the confirm-password Vue page**

`resources/js/Pages/Auth/ConfirmPassword.vue`:
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { setLayoutProps } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })
setLayoutProps({ title: 'Confirm Password' })

const form = useForm({ password: '' })

function submit() {
    form.post(route('password.confirm.store') ?? route('password.confirm'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="rounded-xl border border-border bg-white p-8 shadow-sm">
        <h1 class="mb-2 text-xl font-bold text-foreground">Confirm Password</h1>
        <p class="mb-6 text-sm text-muted-foreground">This is a protected area. Please confirm your password to continue.</p>
        <form class="grid gap-5" @submit.prevent="submit">
            <input v-model="form.password" type="password" autocomplete="current-password" autofocus class="w-full rounded-lg border border-border px-3 py-2 text-sm" placeholder="••••••••" />
            <p v-if="form.errors.password" class="text-xs text-vibrant-coral-600">{{ form.errors.password }}</p>
            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Confirm</button>
        </form>
    </div>
</template>
```
(Use the exact confirmation POST route name recorded in Task 1 Step 4.)

- [ ] **Step 5: Update Task 6's user-store test for re-auth**

`tests/Feature/PasswordPolicyTest.php` now needs a confirmed-password session for `users.store` to be reachable. Add `->withSession(['auth.password_confirmed_at' => now()->timestamp])` to both `actingAs(...)` chains in that file. Re-run:
```bash
php artisan test --compact tests/Feature/PasswordPolicyTest.php
```
Expected: PASS.

- [ ] **Step 6: Run the confirmation tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/PasswordConfirmationTest.php
```
Expected: PASS.

- [ ] **Step 7: Reconcile existing UserManagementTest**

`UserManagementTest` hits `users.create/store/edit/update` and will now redirect to `password.confirm`. In its `beforeEach`, add `->withSession(['auth.password_confirmed_at' => now()->timestamp])` to the acting chain (or a helper), and give the acting SuperAdmin a confirmed 2FA secret so `two-factor.required` also passes. Run:
```bash
php artisan test --compact tests/Feature/UserManagementTest.php
```
Expected: PASS.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: require password re-confirmation for sensitive staff actions"
```

---

## Task 8: Patient (portal) 2FA — enrollment, challenge, enforcement

Builds the parallel patient flow on Fortify's primitives, since Fortify serves only the `web` guard.

**Files:**
- Create: `app/Http/Controllers/Portal/TwoFactorSetupController.php`
- Create: `app/Http/Controllers/Portal/TwoFactorChallengeController.php`
- Create: `app/Http/Middleware/Portal/EnsureTwoFactorEnabled.php`
- Modify: `app/Http/Controllers/Portal/LoginController.php`
- Modify: `bootstrap/app.php` (alias `portal.two-factor.required`)
- Modify: `routes/web.php` (portal 2FA routes + enforcement)
- Create: `resources/js/Pages/Portal/TwoFactorSetup.vue`, `resources/js/Pages/Portal/TwoFactorChallenge.vue`
- Test: `tests/Feature/Portal/TwoFactorTest.php`

**Interfaces:**
- Consumes: `TwoFactorAuthenticatable` on `Patient` (Task 2), `TwoFactorAuthenticationProvider`, `Laravel\Fortify\RecoveryCode`.
- Produces: routes `portal.two-factor.setup`, `portal.two-factor.enable`, `portal.two-factor.confirm`, `portal.two-factor.challenge`, `portal.two-factor.verify`; middleware alias `portal.two-factor.required`.

- [ ] **Step 1: Read the existing portal login controller**

Read `app/Http/Controllers/Portal/LoginController.php` to match its `store()`/`destroy()` conventions and the `portal` guard usage.

- [ ] **Step 2: Write the failing enforcement + challenge tests**

`tests/Feature/Portal/TwoFactorTest.php`:
```php
<?php

use App\Models\Patient;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

function enrolledPatient(string &$secret): Patient
{
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    return Patient::factory()->create([
        'password' => bcrypt('correct-horse-battery-staple-12'),
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);
}

it('forces an un-enrolled patient to setup', function (): void {
    $patient = Patient::factory()->create(['two_factor_confirmed_at' => null]);

    $this->actingAs($patient, 'portal')->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.two-factor.setup'));
});

it('challenges a patient for a code after valid credentials', function (): void {
    $secret = '';
    $patient = enrolledPatient($secret);

    $this->post(route('portal.login.store') ?? route('portal.login'), [
        'email' => $patient->email,
        'password' => 'correct-horse-battery-staple-12',
    ])->assertRedirect(route('portal.two-factor.challenge'));

    expect(auth('portal')->check())->toBeFalse();
});

it('logs the patient in with a valid code', function (): void {
    $secret = '';
    $patient = enrolledPatient($secret);

    $this->post(route('portal.login.store') ?? route('portal.login'), [
        'email' => $patient->email,
        'password' => 'correct-horse-battery-staple-12',
    ]);

    $code = app(TwoFactorAuthenticationProvider::class)->getCurrentOtp($secret);

    $this->post(route('portal.two-factor.verify'), ['code' => $code])->assertRedirect(route('portal.dashboard'));
    expect(auth('portal')->check())->toBeTrue();
});
```
(Fix the portal login route name to the real one.)

- [ ] **Step 3: Run to verify failure**

Run:
```bash
php artisan test --compact tests/Feature/Portal/TwoFactorTest.php
```
Expected: FAIL (routes undefined).

- [ ] **Step 4: Branch login to the challenge**

In `Portal/LoginController@store`, after validating credentials against the `portal` provider WITHOUT logging in (use `Auth::guard('portal')->validate($credentials)` then load the patient), if the patient has `two_factor_confirmed_at`, stash the pending id and redirect to the challenge instead of logging in:
```php
$patient = Patient::where('email', $credentials['email'])->first();

if ($patient && Hash::check($credentials['password'], $patient->password)) {
    if ($patient->two_factor_confirmed_at !== null) {
        $request->session()->put('portal.2fa.pending_id', $patient->id);

        return redirect()->route('portal.two-factor.challenge');
    }

    Auth::guard('portal')->login($patient, $request->boolean('remember'));
    $request->session()->regenerate();

    return redirect()->intended(route('portal.dashboard'));
}

return back()->withErrors(['email' => 'These credentials do not match our records.']);
```
(Preserve the existing throttle and error conventions.)

- [ ] **Step 5: Create the challenge controller**

`app/Http/Controllers/Portal/TwoFactorChallengeController.php`:
```php
<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Portal/TwoFactorChallenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $patientId = $request->session()->get('portal.2fa.pending_id');
        $patient = $patientId ? Patient::find($patientId) : null;

        if ($patient === null) {
            return redirect()->route('portal.login');
        }

        $code = $request->input('code');
        $recovery = $request->input('recovery_code');

        if ($code !== null && $provider->verify(decrypt($patient->two_factor_secret), $code)) {
            return $this->authenticate($request, $patient);
        }

        if ($recovery !== null) {
            $codes = json_decode(decrypt($patient->two_factor_recovery_codes), true);

            if (in_array($recovery, $codes, true)) {
                $patient->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values(array_diff($codes, [$recovery])))),
                ])->save();

                return $this->authenticate($request, $patient);
            }
        }

        return back()->withErrors(['code' => 'The provided code was invalid.']);
    }

    private function authenticate(Request $request, Patient $patient): RedirectResponse
    {
        Auth::guard('portal')->login($patient);
        $request->session()->forget('portal.2fa.pending_id');
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }
}
```

- [ ] **Step 6: Create the setup controller**

`app/Http/Controllers/Portal/TwoFactorSetupController.php` — `create()` renders `Portal/TwoFactorSetup`; `enable()` generates and stores an (unconfirmed) secret + recovery codes; `confirm()` verifies a code and sets `two_factor_confirmed_at`:
```php
<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorSetupController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Portal/TwoFactorSetup');
    }

    public function enable(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $patient = $request->user('portal');
        $secret = $provider->generateSecretKey();

        $patient->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(fn () => RecoveryCode::generate())->all())),
        ])->save();

        return response()->json([
            'svg' => $provider->qrCodeSvg($provider->qrCodeUrl(config('app.name'), $patient->email, $secret)),
            'secret' => $secret,
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $patient = $request->user('portal');
        $request->validate(['code' => ['required', 'string']]);

        if (! $provider->verify(decrypt($patient->two_factor_secret), $request->input('code'))) {
            return back()->withErrors(['code' => 'The provided code was invalid.']);
        }

        $patient->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->route('portal.dashboard');
    }
}
```
(Verify `qrCodeSvg`/`qrCodeUrl` exist on the installed provider via tinker; if the provider only exposes `qrCodeUrl`, render the SVG with `BaconQrCode` directly, mirroring Fortify's `TwoFactorAuthenticatable::twoFactorQrCodeSvg()`.)

- [ ] **Step 7: Create the portal enforcement middleware**

`app/Http/Middleware/Portal/EnsureTwoFactorEnabled.php` — same shape as the staff one but resolves `$request->user('portal')` and redirects to `portal.two-factor.setup`.

- [ ] **Step 8: Register alias and routes**

In `bootstrap/app.php` alias array:
```php
'portal.two-factor.required' => \App\Http\Middleware\Portal\EnsureTwoFactorEnabled::class,
```
In `routes/web.php` `portal` group, add (challenge routes reachable while pending; setup reachable while authenticated-but-unconfirmed; dashboard/messages inside the enforcement group):
```php
Route::middleware('portal.auth')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.verify');

    Route::get('/two-factor-setup', [TwoFactorSetupController::class, 'create'])->name('two-factor.setup');
    Route::post('/two-factor-setup/enable', [TwoFactorSetupController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor-setup/confirm', [TwoFactorSetupController::class, 'confirm'])->name('two-factor.confirm');

    Route::middleware(['portal.two-factor.required', 'idle-timeout:portal,'.env('SESSION_IDLE_TIMEOUT_PORTAL', 30)])->group(function () {
        // existing portal dashboard + messages routes move here
    });
});
```
Note: the challenge routes require `portal.auth` but the patient isn't logged in yet during challenge — instead register the challenge routes OUTSIDE `portal.auth` (they rely on the `portal.2fa.pending_id` session key), and keep setup inside `portal.auth`. Adjust grouping accordingly so the challenge is reachable pre-login.

- [ ] **Step 9: Create the portal Vue pages**

`resources/js/Pages/Portal/TwoFactorChallenge.vue` and `resources/js/Pages/Portal/TwoFactorSetup.vue` — mirror the staff versions (Tasks 3 & 4) but post to the `portal.two-factor.*` routes and use the portal's guest layout (match `Portal/Login.vue`).

- [ ] **Step 10: Run the portal tests**

Run:
```bash
php artisan test --compact tests/Feature/Portal/TwoFactorTest.php
```
Expected: PASS. Fix route names/grouping until green.

- [ ] **Step 11: Reconcile existing portal tests**

`tests/Feature/Portal/DashboardTest.php` and `MessageTest.php` act as a patient and hit protected routes; those patients now need `two_factor_confirmed_at`. Update their patient setup (factory state or a shared helper) to be 2FA-confirmed. Run:
```bash
php artisan test --compact --filter=Portal
```
Expected: PASS.

- [ ] **Step 12: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: patient portal two-factor enrollment, challenge, and enforcement"
```

---

## Task 9: Full-suite reconciliation & wrap-up

**Files:**
- Modify: any remaining feature tests whose acting users now need confirmed 2FA (they hit routes behind `two-factor.required`).
- Optionally: add a `confirmedTwoFactor()` factory state on `UserFactory`/`PatientFactory` to DRY the setup across tests.

- [ ] **Step 1: Add factory states to DRY 2FA setup**

Add to `UserFactory` and `PatientFactory`:
```php
public function twoFactorConfirmed(): static
{
    return $this->state(fn () => [
        'two_factor_secret' => encrypt(app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_confirmed_at' => now(),
    ]);
}
```
Refactor the ad-hoc setups in Tasks 3–8 tests to use `->twoFactorConfirmed()` where it reads cleaner.

- [ ] **Step 2: Run the entire suite**

Run:
```bash
php artisan test --compact
```
Expected: ALL PASS. Any failures are feature tests whose acting user/patient now needs `->twoFactorConfirmed()` (and, for user-management routes, a confirmed-password session). Fix each by adding the state; do not weaken the middleware.

- [ ] **Step 3: Final Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "test: reconcile suite with enforced MFA and add 2FA factory states"
```

---

## Notes for the implementer

- **Fortify route/method drift:** Fortify's exact route names and `TwoFactorAuthenticationProvider`/`RecoveryCode` method signatures can vary slightly by version. Task 1 Step 4 records the real names; Tasks 2/6/8 include tinker-verification fallbacks for provider methods. Always trust `php artisan route:list` and `php artisan tinker` over this document where they disagree.
- **`uncompromised()` in tests:** it calls HaveIBeenPwned over the network. If CI must be offline, gate `uncompromised()` behind `app()->isProduction()` in `Password::defaults()` (per the Laravel docs pattern) or `Http::fake()` in the affected tests.
- **Existing accounts:** after this ships, every seeded staff/patient is forced through 2FA setup on next login — expected and covered by the enforcement tests.
- **Order matters:** middleware order within a group is `two-factor.required` (or `portal.two-factor.required`) before `idle-timeout`; both after `auth`/`portal.auth`.
