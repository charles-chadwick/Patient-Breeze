# Global "Not Authorized" Modal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the raw 403 error response with a friendly, app-wide "Not Authorized" modal that keeps the user on the current page.

**Architecture:** Client-side interception of Inertia v3's `httpException` event. A server guard keeps 403s as raw (non-Inertia) responses so the event fires in every environment. A module-scoped reactive singleton composable holds modal state; a global `httpException` listener in `app.js` drives it; a `Dialog`-based component mounted once in `DashboardLayout` renders it.

**Tech Stack:** Laravel 13, Inertia.js v3, Vue 3, shadcn-vue (`Dialog`, `Button`), Pest 4 (browser testing).

## Global Constraints

- Naming: variables `snake_case`, methods/functions `camelCase`, classes `TitleCase`.
- Use descriptive names even in closures (e.g. `fn ($query) => ...`, not `fn ($q) => ...`).
- PHP: curly braces on all control structures; explicit return types and param type hints; constructor property promotion.
- Reuse existing shadcn `Dialog`/`Button` primitives (same set used by `resources/js/Components/ContactModal.vue`). Do not add dependencies.
- Only status `403` is intercepted client-side; all other statuses keep Inertia's default behavior.
- Run `vendor/bin/pint --dirty --format agent` after any PHP change.
- Modal copy (verbatim default): `You are not authorized to perform this action.`
- Modal title (verbatim): `Not Authorized`.

---

### Task 1: Server guard — keep 403s as raw responses

**Files:**
- Modify: `bootstrap/app.php:26-28` (the `withExceptions` closure)
- Test: `tests/Feature/AuthorizationTest.php` (exists; append)

**Interfaces:**
- Consumes: nothing.
- Produces: guarantees an `AuthorizationException`/403 leaves the server as a non-Inertia response (no `X-Inertia` header, not a rendered `ErrorPage`). The client listener in Task 3 depends on this.

**Context:** `bootstrap/app.php` currently has an empty `withExceptions` closure. Inertia v3 auto-registers exception handling that, outside `local`/`testing`, converts 403 into a rendered `ErrorPage` Inertia response — which would suppress `httpException` and fail here (no `ErrorPage.vue`). Registering our own `respond` callback that returns the response untouched keeps 403 a raw response. Inspect the existing `tests/Feature/AuthorizationTest.php` first and follow its setup conventions (it already exercises policy 403s).

- [ ] **Step 1: Read the existing authorization test to reuse its setup**

Run: `sed -n '1,60p' tests/Feature/AuthorizationTest.php`
Note how it seeds roles/permissions and creates a user without a given permission.

- [ ] **Step 2: Write the failing test**

Append to `tests/Feature/AuthorizationTest.php`. Adjust the user-creation/permission-revocation lines to match the file's existing helpers (e.g. a factory state or a role without `create_appointments`). A Doctor lacks no appointment perms, so pick a role that does — Staff has `create_appointments`; instead create a user with a role stripped of it, or use the same pattern the file already uses for asserting 403.

```php
test('a 403 is returned as a non-inertia response so the client can intercept it', function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $patient = \App\Models\Patient::factory()->create();

    // A user whose role lacks create_appointments. Mirror the existing
    // unauthorized-user setup already used elsewhere in this file.
    $user = \App\Models\User::factory()->create();
    $user->assignRole(\App\Enums\UserRole::Staff->value);
    $user->revokePermissionTo('create_appointments');

    $response = $this
        ->actingAs($user)
        ->get(route('patients.appointments.create', $patient));

    $response->assertForbidden();               // 403
    $response->assertHeaderMissing('X-Inertia'); // raw, not an Inertia error page
});
```

Note: confirm the appointment create route name via `php artisan route:list --path=appointments`. Replace `patients.appointments.create` with the actual name if it differs.

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter='non-inertia response'`
Expected: FAIL — in `testing` env the raw 403 may already pass the header check, so the guard's real value is production parity. If it already passes on the header, keep the test (it locks in the contract) and proceed; the guard below makes the behavior explicit and environment-independent.

- [ ] **Step 4: Add the server guard**

Edit `bootstrap/app.php`. Add the `Response` import and the `respond` callback:

```php
use Symfony\Component\HttpFoundation\Response;
```

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->respond(function (Response $response): Response {
        return $response;
    });
})
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter='non-inertia response'`
Expected: PASS

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add bootstrap/app.php tests/Feature/AuthorizationTest.php
git commit -m "feat: keep 403 responses non-inertia so client can intercept them"
```

---

### Task 2: Shared modal state composable

**Files:**
- Create: `resources/js/composables/useAuthorizationModal.js`

**Interfaces:**
- Consumes: `reactive` from `vue`.
- Produces: `useAuthorizationModal()` returning `{ state, showDenied, dismiss }` where:
  - `state` is a reactive singleton object `{ is_open: boolean, message: string }`
  - `showDenied(message?: string): void` sets `message` (default `You are not authorized to perform this action.`) and `is_open = true`
  - `dismiss(): void` sets `is_open = false`
  - The SAME `state` instance is shared across all calls (module-scoped singleton).

- [ ] **Step 1: Create the composable**

Create `resources/js/composables/useAuthorizationModal.js`:

```js
import { reactive } from 'vue'

const state = reactive({
    is_open: false,
    message: '',
})

const DEFAULT_MESSAGE = 'You are not authorized to perform this action.'

export function useAuthorizationModal() {
    function showDenied(message = DEFAULT_MESSAGE) {
        state.message = message
        state.is_open = true
    }

    function dismiss() {
        state.is_open = false
    }

    return { state, showDenied, dismiss }
}
```

- [ ] **Step 2: Sanity-check the build compiles**

Run: `npm run build`
Expected: builds without errors (the new file is valid ES module syntax). If the project uses `npm run dev` only, skip the full build and rely on Task 5's browser test.

- [ ] **Step 3: Commit**

```bash
git add resources/js/composables/useAuthorizationModal.js
git commit -m "feat: add useAuthorizationModal shared state composable"
```

---

### Task 3: Global httpException listener

**Files:**
- Modify: `resources/js/app.js:1-23`

**Interfaces:**
- Consumes: `useAuthorizationModal` from Task 2 (`{ showDenied }`); `router` from `@inertiajs/vue3`.
- Produces: on any 403 non-Inertia response, prevents Inertia's default modal and opens the authorization modal.

**Context:** `app.js` currently imports `createInertiaApp` and mounts the app. Add a top-level `router.on('httpException', ...)` listener. `event.detail.response` is the raw response; guard on `status === 403` and only `preventDefault()` in that case so other statuses (404/500) keep default handling.

- [ ] **Step 1: Add imports**

In `resources/js/app.js`, add to the imports at the top:

```js
import { createInertiaApp, router } from '@inertiajs/vue3';
import { useAuthorizationModal } from '@/composables/useAuthorizationModal';
```

(Merge `router` into the existing `@inertiajs/vue3` import line; do not duplicate the import.)

- [ ] **Step 2: Register the listener**

Add after the `createInertiaApp({ ... })` call in `resources/js/app.js`:

```js
router.on('httpException', (event) => {
    if (event.detail.response?.status === 403) {
        event.preventDefault();
        useAuthorizationModal().showDenied();
    }
});
```

- [ ] **Step 3: Verify build**

Run: `npm run build`
Expected: builds without errors. (The `@` alias already resolves to `resources/js` — it is used by existing components like `ContactModal.vue`.)

- [ ] **Step 4: Commit**

```bash
git add resources/js/app.js
git commit -m "feat: intercept 403 responses and open authorization modal"
```

---

### Task 4: AuthorizationModal component + mount in DashboardLayout

**Files:**
- Create: `resources/js/Components/AuthorizationModal.vue`
- Modify: `resources/js/Layouts/DashboardLayout.vue` (script imports + template)

**Interfaces:**
- Consumes: `useAuthorizationModal` from Task 2 (`{ state, dismiss }`); shadcn `Dialog`/`Button` primitives.
- Produces: a self-contained global modal component with no props, rendered once in the authenticated layout.

**Context:** Mirror `resources/js/Components/ContactModal.vue`'s use of `Dialog`, `DialogContent`, `DialogHeader`, `DialogTitle`, `DialogDescription`, `DialogFooter`. Available exports are confirmed in `resources/js/Components/ui/dialog/index.js` and `resources/js/Components/ui/button/index.js`. `DashboardLayout.vue` renders `<slot />` at line ~132 inside its root; add the modal as a sibling of the slot.

- [ ] **Step 1: Create the component**

Create `resources/js/Components/AuthorizationModal.vue`:

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
import { Button } from '@/Components/ui/button'
import { useAuthorizationModal } from '@/composables/useAuthorizationModal'

const { state, dismiss } = useAuthorizationModal()

function handleOpenUpdate(value) {
    if (!value) {
        dismiss()
    }
}
</script>

<template>
    <Dialog :open="state.is_open" @update:open="handleOpenUpdate">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Not Authorized</DialogTitle>
                <DialogDescription>{{ state.message }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="dismiss">OK</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 2: Import the component in DashboardLayout**

In `resources/js/Layouts/DashboardLayout.vue`, add to the `<script setup>` imports (near the existing `@inertiajs/vue3` import at line ~3):

```js
import AuthorizationModal from '@/Components/AuthorizationModal.vue'
```

- [ ] **Step 3: Render the modal in the layout template**

In `resources/js/Layouts/DashboardLayout.vue`, add `<AuthorizationModal />` immediately after the `<slot />` (around line 132), still inside the layout's single root element:

```vue
                <slot />
                <AuthorizationModal />
```

(Match the surrounding indentation exactly. Keep a single root element — add the modal inside the existing wrapper, not as a second root.)

- [ ] **Step 4: Verify build**

Run: `npm run build`
Expected: builds without errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/AuthorizationModal.vue resources/js/Layouts/DashboardLayout.vue
git commit -m "feat: add AuthorizationModal and mount it in the dashboard layout"
```

---

### Task 5: Browser test for the end-to-end flow

**Files:**
- Create: `tests/Browser/AuthorizationModalTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1-4; `RoleAndPermissionSeeder`; the appointment create route.

**Context:** Pest 4 browser tests live under `tests/Browser`. Check for an existing browser test to copy conventions (`ls tests/Browser`); if none exist, this is the first — use `visit()` from Pest 4's browser plugin. The test logs in as a user lacking `create_appointments`, drives the scheduling action, and asserts the modal appears without navigating to an error page.

- [ ] **Step 1: Inspect existing browser tests and the create route**

Run: `ls tests/Browser 2>/dev/null; php artisan route:list --path=appointments`
Note the exact route name and any existing browser-test setup pattern.

- [ ] **Step 2: Write the browser test**

Create `tests/Browser/AuthorizationModalTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('an unauthorized user sees the not-authorized modal instead of a 403 page', function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(UserRole::Staff->value);
    $user->revokePermissionTo('create_appointments');

    $patient = Patient::factory()->create();

    actingAs($user);

    $page = visit(route('patients.appointments.create', $patient));

    $page->assertSee('Not Authorized')
        ->assertSee('You are not authorized to perform this action.')
        ->assertDontSee('403');
})->group('browser');
```

Adjust the route name to match Step 1's output. If the appointment create page is only reachable via a button on the patient page, drive it with `->click(...)` from the patient view instead of visiting the route directly — whichever reflects a real user path.

- [ ] **Step 3: Run the test to verify it passes**

Run: `php artisan test --compact tests/Browser/AuthorizationModalTest.php`
Expected: PASS — modal text visible, no error page. If it fails because the client bundle is stale, run `npm run build` first, then re-run.

- [ ] **Step 4: Commit**

```bash
git add tests/Browser/AuthorizationModalTest.php
git commit -m "test: verify unauthorized users see the not-authorized modal"
```

---

## Notes for the implementer

- The underlying permission gap (the DB was seeded before appointment permissions existed) is a **separate** issue. Re-seeding via `php artisan db:seed --class=RoleAndPermissionSeeder` fixes real Super Admin access, but this plan is only about the modal UX. The tests here deliberately construct an unauthorized user so they stay valid regardless of seeding.
- If `route('patients.appointments.create', ...)` is not the actual route name, use the name from `php artisan route:list --path=appointments` consistently in Tasks 1 and 5.
