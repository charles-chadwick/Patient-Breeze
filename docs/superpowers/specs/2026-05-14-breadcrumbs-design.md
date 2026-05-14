# Breadcrumbs Design

**Date:** 2026-05-14
**Status:** Approved

## Summary

Add a breadcrumb trail to the `<header>` area of `DashboardLayout`. Each page explicitly defines its breadcrumbs via `setLayoutProps()`. When breadcrumbs are present they replace the existing `<h1>` page title; when absent the title renders as before. Existing inline "← Back to X" links are removed from all page content areas.

## Data Shape

Each crumb is `{ label: string, href?: string }`. The last crumb has no `href` (current page). All prior crumbs are linked.

```ts
// { label: string, href?: string }[]
setLayoutProps({
    breadcrumbs: [
        { label: 'Patients', href: route('patients.index') },
        { label: 'John Smith' }, // current page — no href
    ],
})
```

## DashboardLayout Changes

- Add `breadcrumbs: { type: Array, default: () => [] }` prop alongside the existing `title` prop.
- In the header, replace `<h1>{{ title }}</h1>` with a conditional:
  - **breadcrumbs present:** `<nav>` with crumbs joined by a `/` separator. Linked crumbs use `<Link>`, the last (current page) is a plain `<span>` styled to match the current title weight/colour.
  - **no breadcrumbs:** keep the existing `<h1>{{ title }}</h1>` as fallback (used by Dashboard).

## Pages Updated

| Page | Breadcrumbs |
|---|---|
| `Dashboard` | *(no breadcrumbs — title fallback)* |
| `Patients/Index` | Patients |
| `Patients/Form` (create) | Patients / New Patient |
| `Patients/Form` (edit) | Patients / [First Last] / Edit [First Last] |
| `Patients/Show` | Patients / [First Last] |
| `Appointments/Index` | Appointments |
| `Appointments/Form` (create) | Patients / [First Last] / New Appointment |
| `Appointments/Form` (edit) | Patients / [First Last] / Edit Appointment |
| `Users/Index` | Users |
| `Users/Form` (create) | Users / New User |
| `Users/Form` (edit) | Users / [First Last] / Edit [First Last] |
| `Users/Show` | Users / [First Last] |

All pages that currently contain an inline `← Back to X` link have it removed.

## Pages NOT Updated

- `Contacts/Index` — no breadcrumb needed (no parent page)
- `Auth/Login` / `GuestLayout` — outside the dashboard layout entirely

## Styling

Linked crumbs: `text-sm text-muted-foreground hover:text-foreground` with `<Link>`.
Separator: `text-sm text-muted-foreground mx-1` — literal `/`.
Current crumb (last): `text-lg font-bold text-foreground` — matches the existing `<h1>` style.

## Testing

- Add a feature test asserting that pages rendering breadcrumbs pass the correct `breadcrumbs` prop to the layout (via Inertia page props).
- Or, since Inertia page props are already exercised by existing controller tests, verify breadcrumb data is correct by asserting `setLayoutProps` values in existing page component tests if present.
