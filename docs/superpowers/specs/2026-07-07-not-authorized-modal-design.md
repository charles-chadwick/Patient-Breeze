# Global "Not Authorized" Modal — Design

**Date:** 2026-07-07
**Status:** Approved

## Problem

When a user attempts an action they lack permission for (e.g. scheduling an
appointment without the `create_appointments` permission), the controller's
`$this->authorize(...)` throws an `AuthorizationException` (HTTP 403). Today that
surfaces as a raw 403 error response — a poor experience. We want a modal to
appear with a clear "you are not authorized" message, keeping the user on the
current page.

## Goals

- Any 403 authorization failure, app-wide, shows a friendly modal instead of the
  raw error response or an error page.
- The user stays on the page they were on — no navigation away.
- Works in every environment (local, testing, production).

## Non-Goals

- Fixing the underlying permission/seeding gap (tracked separately).
- Handling non-403 HTTP errors (404, 419, 500). Those keep Inertia's default
  behavior.
- Portal (patient-facing) 403 handling. Web-user 403s all occur under the
  authenticated dashboard.

## Approach

Client-side interception via Inertia v3's `httpException` event, with a small
server-side guard so the event fires in production too.

### Why the server guard is required

The `httpException` event only fires when the server returns a **non-Inertia**
response. In `local`/`testing`, a 403 comes back as a raw response, so the event
fires. But Inertia v3's default exception handling converts a 403 into a
rendered `ErrorPage` Inertia response in production — which would both suppress
`httpException` and fail here (no `ErrorPage.vue` exists). The guard keeps 403s
as raw responses so the client handler runs everywhere.

## Components

### 1. Server guard — `bootstrap/app.php`

Within the existing `withExceptions(...)` closure, register a `respond` callback
that returns 403 responses unchanged (prevents Inertia from rendering an error
page for 403). All other statuses keep default behavior.

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->respond(function (Response $response): Response {
        return $response;
    });
})
```

The exact predicate is finalized during implementation; the requirement is:
a 403 leaves the server as a non-Inertia response so `httpException` fires.

### 2. Shared modal state — `resources/js/composables/useAuthorizationModal.js`

A module-scoped reactive singleton so `app.js` (running outside the Vue app) can
drive a modal that is rendered inside the Vue app.

```js
import { reactive } from 'vue'

const state = reactive({
    is_open: false,
    message: '',
})

export function useAuthorizationModal() {
    function showDenied(message = 'You are not authorized to perform this action.') {
        state.message = message
        state.is_open = true
    }

    function dismiss() {
        state.is_open = false
    }

    return { state, showDenied, dismiss }
}
```

Naming follows project conventions: `snake_case` state, `camelCase` methods.

### 3. Global listener — `resources/js/app.js`

Register a single `httpException` listener. Intercept only status 403; call
`preventDefault()` to stop Inertia's default non-Inertia-response modal, then
open the shared modal.

```js
import { router } from '@inertiajs/vue3'
import { useAuthorizationModal } from '@/composables/useAuthorizationModal'

router.on('httpException', (event) => {
    if (event.detail.response?.status === 403) {
        event.preventDefault()
        useAuthorizationModal().showDenied()
    }
})
```

### 4. Modal component — `resources/js/Components/AuthorizationModal.vue`

Built on the existing shadcn `Dialog` primitives (same set used by
`ContactModal.vue`) plus `Button`. Binds `open` to the composable's
`state.is_open`, shows `state.message`, and provides a single dismiss action.
No props required — it reads global state directly.

Structure:
- `Dialog` with `:open="state.is_open"` and `@update:open` → `dismiss()`
- `DialogContent` → `DialogHeader` (`DialogTitle` "Not Authorized",
  `DialogDescription` `{{ state.message }}`)
- `DialogFooter` → `Button` "OK" → `dismiss()`

### 5. Mount point — `resources/js/Layouts/DashboardLayout.vue`

Render `<AuthorizationModal />` once, alongside the existing `<slot />`, so it is
globally available on every authenticated page. Import added to the layout's
script block.

## Data Flow

```
User action (GET link / POST form)
  → controller $this->authorize(...) fails → 403 (raw, non-Inertia response)
  → Inertia receives non-Inertia response → fires `httpException`
  → app.js listener: status === 403 → preventDefault() + showDenied()
  → useAuthorizationModal state.is_open = true
  → <AuthorizationModal> in DashboardLayout renders the Dialog
  → user clicks OK → dismiss() → state.is_open = false; user stays on page
```

## Testing

A **Pest 4 browser test** (`tests/Browser`) covering the primary flow:

1. Seed roles/permissions and create a user whose role lacks
   `create_appointments` (e.g. a Staff user restricted for the test, or a role
   with the permission revoked).
2. Log in and drive the appointment-scheduling action.
3. Assert the "Not Authorized" modal text is visible.
4. Assert the browser did **not** navigate to an error page (URL unchanged / no
   raw 403 body).

If a browser test proves impractical for the 403 path, fall back to a feature
test asserting the server returns a raw non-Inertia 403 (validating the server
guard), plus a component-level assertion of the modal wiring.

## Risks / Notes

- `httpException` fires for all non-Inertia responses (including 500/404). The
  listener must guard on `status === 403` only, and must not `preventDefault()`
  for other statuses, so their default handling is preserved.
- The reactive singleton is intentionally a module-level instance shared across
  all `useAuthorizationModal()` calls; do not instantiate per-component state.
