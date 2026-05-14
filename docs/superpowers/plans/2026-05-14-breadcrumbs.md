# Breadcrumbs Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a breadcrumb trail to the header of `DashboardLayout` driven by per-page `setLayoutProps()` calls, replacing the current plain title and removing inline "← Back to X" links.

**Architecture:** A `breadcrumbs` prop (array of `{ label, href? }`) is added to `DashboardLayout`. When present, the header renders a `<nav>` with linked and current-page crumbs instead of the `<h1>`. Each page sets its own crumbs via `setLayoutProps()`, the same Inertia v3 mechanism already used for `title`. Dashboard uses no breadcrumbs and keeps the title fallback.

**Tech Stack:** Vue 3, Inertia.js v3 (`setLayoutProps`), Tailwind CSS v4, Ziggy (`route()`)

---

## File Map

| File | Change |
|---|---|
| `resources/js/Layouts/DashboardLayout.vue` | Add `breadcrumbs` prop; conditional header rendering |
| `resources/js/Pages/Patients/Index.vue` | Add single-crumb breadcrumb |
| `resources/js/Pages/Patients/Show.vue` | Add breadcrumbs; remove back link; simplify toolbar |
| `resources/js/Pages/Patients/Form.vue` | Add computed breadcrumbs; remove back link div |
| `resources/js/Pages/Appointments/Index.vue` | Add single-crumb breadcrumb |
| `resources/js/Pages/Appointments/Form.vue` | Add computed breadcrumbs; remove back link div |
| `resources/js/Pages/Users/Index.vue` | Add single-crumb breadcrumb |
| `resources/js/Pages/Users/Show.vue` | Add breadcrumbs; remove back link; simplify toolbar |
| `resources/js/Pages/Users/Form.vue` | Add computed breadcrumbs; remove back link div |

---

## Task 1: Update DashboardLayout to support breadcrumbs

**Files:**
- Modify: `resources/js/Layouts/DashboardLayout.vue`

- [ ] **Step 1: Add `breadcrumbs` prop and update the header template**

  Replace the `defineProps` block and the `<header>` section. The full updated file:

  ```vue
  <script setup>
  import { ref } from 'vue'
  import { Head, Link, router, usePage } from '@inertiajs/vue3'
  import { LayoutDashboard, HeartPulse, CalendarDays, Users, Settings, Menu, X, LogOut } from 'lucide-vue-next'

  defineProps({
      title: {
          type: String,
          default: 'Dashboard',
      },
      breadcrumbs: {
          type: Array,
          default: () => [],
      },
  })

  const nav_items = [
      { label: 'Dashboard', route: 'dashboard', icon: LayoutDashboard },
      { label: 'Patients', route: 'patients.index', icon: HeartPulse },
      { label: 'Appointments', route: 'appointments.index', icon: CalendarDays },
      { label: 'Users', route: 'users.index', icon: Users },
      { label: 'Settings', route: 'settings.index', icon: Settings },
  ]

  const page = usePage()
  const sidebar_open = ref(false)
  </script>

  <template>
      <Head :title="title" />
      <div class="flex h-screen overflow-hidden">
          <!-- Mobile backdrop -->
          <div
              v-if="sidebar_open"
              class="fixed inset-0 z-20 bg-black/50 lg:hidden"
              @click="sidebar_open = false"
          />

          <!-- Sidebar -->
          <aside
              class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-primary transition-transform duration-300 lg:static lg:translate-x-0 lg:transition-none"
              :class="sidebar_open ? 'translate-x-0' : '-translate-x-full'"
          >
              <!-- Logo -->
              <div class="flex h-16 items-center justify-between px-6">
                  <span class="text-xl font-bold text-white">PB Health</span>
                  <button
                      class="rounded p-1 text-white/70 hover:text-white lg:hidden"
                      @click="sidebar_open = false"
                  >
                      <X class="size-5" />
                  </button>
              </div>

              <!-- Navigation -->
              <nav class="flex flex-1 flex-col gap-1 px-3 py-4">
                  <Link
                      v-for="item in nav_items"
                      :key="item.route"
                      :href="route().has(item.route) ? route(item.route) : '#'"
                      class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                      :class="{ 'bg-white/15 text-white': route().current(item.route) }"
                      @click="sidebar_open = false"
                  >
                      <component :is="item.icon" class="size-4 shrink-0 text-white" />
                      <span>{{ item.label }}</span>
                  </Link>
              </nav>

              <!-- User footer -->
              <div class="border-t border-white/20 px-4 py-4">
                  <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white">
                          {{ page.props.auth?.user?.first_name?.[0] ?? 'U' }}
                      </div>
                      <div class="flex-1 overflow-hidden">
                          <p class="truncate text-sm font-bold text-white">
                              {{ page.props.auth?.user?.first_name }} {{ page.props.auth?.user?.last_name }}
                          </p>
                          <p class="truncate text-xs text-white/60">
                              {{ page.props.auth?.user?.email }}
                          </p>
                      </div>
                      <button
                          class="rounded p-1 text-white/60 hover:text-white"
                          title="Sign out"
                          @click="router.post(route('logout'))"
                      >
                          <LogOut class="size-4" />
                      </button>
                  </div>
              </div>
          </aside>

          <!-- Main content -->
          <div class="flex flex-1 flex-col overflow-hidden">
              <!-- Top bar -->
              <header class="flex h-16 items-center gap-4 border-b border-border bg-white px-6">
                  <button
                      class="rounded p-1 text-foreground hover:text-primary lg:hidden"
                      @click="sidebar_open = true"
                  >
                      <Menu class="size-5" />
                  </button>
                  <nav v-if="breadcrumbs.length" class="flex items-center">
                      <template v-for="(crumb, index) in breadcrumbs" :key="index">
                          <span v-if="index > 0" class="mx-1.5 text-sm text-muted-foreground">/</span>
                          <Link
                              v-if="crumb.href"
                              :href="crumb.href"
                              class="text-sm text-muted-foreground hover:text-foreground"
                          >{{ crumb.label }}</Link>
                          <span
                              v-else
                              class="text-lg font-bold text-foreground"
                          >{{ crumb.label }}</span>
                      </template>
                  </nav>
                  <h1 v-else class="text-lg font-bold text-foreground">{{ title }}</h1>
              </header>

              <!-- Page content -->
              <main class="flex-1 overflow-y-auto bg-background p-6">
                  <slot />
              </main>
          </div>
      </div>
  </template>
  ```

- [ ] **Step 2: Run Pint to format**

  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **Step 3: Run the test suite**

  ```bash
  php artisan test --compact
  ```

  Expected: all tests pass (no PHP code was changed).

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Layouts/DashboardLayout.vue
  git commit -m "feat: add breadcrumbs support to DashboardLayout"
  ```

---

## Task 2: Add breadcrumbs to Patients pages

**Files:**
- Modify: `resources/js/Pages/Patients/Index.vue`
- Modify: `resources/js/Pages/Patients/Show.vue`
- Modify: `resources/js/Pages/Patients/Form.vue`

- [ ] **Step 1: Update Patients/Index.vue**

  Change the `setLayoutProps` call (currently `setLayoutProps({ title: 'Patients' })`):

  ```js
  setLayoutProps({
      breadcrumbs: [
          { label: 'Patients' },
      ],
  })
  ```

  The `title` prop is no longer needed for this page since the last crumb carries the label.

- [ ] **Step 2: Update Patients/Show.vue — breadcrumbs and toolbar**

  Replace the `setLayoutProps` call:
  ```js
  // Before:
  setLayoutProps({
      title: `${props.patient.first_name} ${props.patient.last_name}`,
  })

  // After:
  setLayoutProps({
      breadcrumbs: [
          { label: 'Patients', href: route('patients.index') },
          { label: `${props.patient.first_name} ${props.patient.last_name}` },
      ],
  })
  ```

  Then in the template, remove the "← Back to Patients" link and simplify the toolbar div. Replace:
  ```vue
  <div class="flex items-center justify-between">
      <Link
          :href="route('patients.index')"
          class="text-sm font-bold text-primary hover:underline"
      >
          ← Back to Patients
      </Link>
      <Link
          :href="route('patients.edit', patient.id)"
          class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
      >
          Edit Patient
      </Link>
  </div>
  ```

  With:
  ```vue
  <div class="flex justify-end">
      <Link
          :href="route('patients.edit', patient.id)"
          class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-bold text-foreground hover:bg-muted/40"
      >
          Edit Patient
      </Link>
  </div>
  ```

- [ ] **Step 3: Update Patients/Form.vue — breadcrumbs and remove back link**

  Replace the `setLayoutProps` call. Note: `backHref` must be kept — it's still used as `cancel-href` for the form component.

  ```js
  // Before:
  setLayoutProps({
      title: computed(() =>
          isEditing.value
              ? `Edit ${props.patient.first_name} ${props.patient.last_name}`
              : 'New Patient'
      ),
  })

  // After:
  setLayoutProps({
      breadcrumbs: computed(() => {
          if (isEditing.value) {
              return [
                  { label: 'Patients', href: route('patients.index') },
                  { label: `${props.patient.first_name} ${props.patient.last_name}`, href: route('patients.show', props.patient.id) },
                  { label: `Edit ${props.patient.first_name} ${props.patient.last_name}` },
              ]
          }
          return [
              { label: 'Patients', href: route('patients.index') },
              { label: 'New Patient' },
          ]
      }),
  })
  ```

  Then in the template, remove the back link div. Replace:
  ```vue
  <div>
      <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
          {{ isEditing ? '← Back to Patient' : '← Back to Patients' }}
      </Link>
  </div>
  ```

  With nothing (delete the entire `<div>` block). `<PatientForm>` becomes the first child of `<div class="grid gap-6">`.

  Keep `backHref` — it's still passed as `:cancel-href="backHref"` to `<PatientForm>`.

- [ ] **Step 4: Run the test suite**

  ```bash
  php artisan test --compact
  ```

  Expected: all tests pass.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Patients/Index.vue resources/js/Pages/Patients/Show.vue resources/js/Pages/Patients/Form.vue
  git commit -m "feat: add breadcrumbs to Patients pages"
  ```

---

## Task 3: Add breadcrumbs to Appointments pages

**Files:**
- Modify: `resources/js/Pages/Appointments/Index.vue`
- Modify: `resources/js/Pages/Appointments/Form.vue`

- [ ] **Step 1: Update Appointments/Index.vue**

  Change `setLayoutProps ( { title: 'Appointments' } )` to:

  ```js
  setLayoutProps({
      breadcrumbs: [
          { label: 'Appointments' },
      ],
  })
  ```

- [ ] **Step 2: Update Appointments/Form.vue — breadcrumbs and remove back link**

  Replace `setLayoutProps`:
  ```js
  // Before:
  setLayoutProps({
      title: computed(() =>
          isEditing.value ? 'Edit Appointment' : 'New Appointment'
      ),
  })

  // After:
  setLayoutProps({
      breadcrumbs: computed(() => [
          { label: 'Patients', href: route('patients.index') },
          { label: `${props.patient.first_name} ${props.patient.last_name}`, href: route('patients.show', props.patient.id) },
          { label: isEditing.value ? 'Edit Appointment' : 'New Appointment' },
      ]),
  })
  ```

  In the template, remove the back link div. Replace:
  ```vue
  <div>
      <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
          ← Back to {{ patient.first_name }} {{ patient.last_name }}
      </Link>
  </div>
  ```

  With nothing. The `<PatientCard>` becomes the first child. Keep `backHref` and the `:cancel-href="backHref"` prop on `<AppointmentForm>`.

- [ ] **Step 3: Run the test suite**

  ```bash
  php artisan test --compact
  ```

  Expected: all tests pass.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Pages/Appointments/Index.vue resources/js/Pages/Appointments/Form.vue
  git commit -m "feat: add breadcrumbs to Appointments pages"
  ```

---

## Task 4: Add breadcrumbs to Users pages

**Files:**
- Modify: `resources/js/Pages/Users/Index.vue`
- Modify: `resources/js/Pages/Users/Show.vue`
- Modify: `resources/js/Pages/Users/Form.vue`

- [ ] **Step 1: Update Users/Index.vue**

  Change `setLayoutProps({ title: 'Users' })` to:

  ```js
  setLayoutProps({
      breadcrumbs: [
          { label: 'Users' },
      ],
  })
  ```

- [ ] **Step 2: Update Users/Show.vue — breadcrumbs and toolbar**

  Replace `setLayoutProps`:
  ```js
  // Before:
  setLayoutProps({
      title: `${props.user.first_name} ${props.user.last_name}`,
  })

  // After:
  setLayoutProps({
      breadcrumbs: [
          { label: 'Users', href: route('users.index') },
          { label: `${props.user.first_name} ${props.user.last_name}` },
      ],
  })
  ```

  In the template, remove the "← Back to Users" link and simplify the toolbar. Replace:
  ```vue
  <div class="flex items-center justify-between">
      <Link
          :href="route('users.index')"
          class="text-sm font-bold text-primary hover:underline"
      >
          ← Back to Users
      </Link>
      <Link
          :href="route('users.edit', user.id)"
          class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
      >
          Edit User
      </Link>
  </div>
  ```

  With:
  ```vue
  <div class="flex justify-end">
      <Link
          :href="route('users.edit', user.id)"
          class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
      >
          Edit User
      </Link>
  </div>
  ```

- [ ] **Step 3: Update Users/Form.vue — breadcrumbs and remove back link**

  Replace `setLayoutProps`:
  ```js
  // Before:
  setLayoutProps({
      title: computed(() =>
          isEditing.value
              ? `Edit ${props.user.first_name} ${props.user.last_name}`
              : 'New User'
      ),
  })

  // After:
  setLayoutProps({
      breadcrumbs: computed(() => {
          if (isEditing.value) {
              return [
                  { label: 'Users', href: route('users.index') },
                  { label: `${props.user.first_name} ${props.user.last_name}`, href: route('users.show', props.user.id) },
                  { label: `Edit ${props.user.first_name} ${props.user.last_name}` },
              ]
          }
          return [
              { label: 'Users', href: route('users.index') },
              { label: 'New User' },
          ]
      }),
  })
  ```

  In the template, remove the back link div. Replace:
  ```vue
  <div>
      <Link :href="backHref" class="text-sm font-bold text-primary hover:underline">
          ← Back to Users
      </Link>
  </div>
  ```

  With nothing. Keep `backHref` and the `:cancel-href="backHref"` prop on `<UserForm>`.

- [ ] **Step 4: Run the test suite**

  ```bash
  php artisan test --compact
  ```

  Expected: all tests pass.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Users/Index.vue resources/js/Pages/Users/Show.vue resources/js/Pages/Users/Form.vue
  git commit -m "feat: add breadcrumbs to Users pages"
  ```
