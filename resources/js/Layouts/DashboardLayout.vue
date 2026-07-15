<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { LayoutDashboard, HeartPulse, CalendarDays, Users, Settings, Menu, X, LogOut, Inbox, ChevronUp, ChevronDown, ShieldCheck, Monitor, Sun, Moon, Pill, Stethoscope, TestTubes, TriangleAlert, FileSignature, ScrollText } from 'lucide-vue-next'
import ErrorModal from '@/Components/ErrorModal.vue'
import NotificationBell from '@/Components/NotificationBell.vue'
import Toaster from '@/Components/Toaster.vue'
import { applyTheme } from '@/theme'

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
})

const effective_title = computed(() =>
    props.breadcrumbs.length
        ? props.breadcrumbs[props.breadcrumbs.length - 1].label
        : props.title
)

// Computed so labels re-evaluate once the async language file has loaded.
const nav_items = computed(() => [
    { label: trans('nav.dashboard'), route: 'dashboard', icon: LayoutDashboard },
    { label: trans('nav.patients'), route: 'patients.index', icon: HeartPulse },
    { label: trans('nav.appointments'), route: 'appointments.index', icon: CalendarDays },
    { label: trans('nav.portal_queue'), route: 'portal-queue.index', icon: Inbox },
])

// Collapsible section groups. Each child is a regular nav link.
const nav_sections = computed(() => [
    {
        label: trans('nav.administration'),
        icon: ShieldCheck,
        children: [
            { label: trans('nav.users'), route: 'users.index', icon: Users },
            { label: trans('nav.medications'), route: 'medications.index', icon: Pill },
            { label: trans('nav.diagnoses'), route: 'diagnoses.index', icon: Stethoscope },
            { label: trans('nav.allergens'), route: 'allergens.index', icon: TriangleAlert },
            { label: trans('nav.lab_orders'), route: 'lab-orders.index', icon: TestTubes },
            { label: trans('nav.audit_log'), route: 'audit-log.index', icon: ScrollText },
        ],
    },
])

const page = usePage()
const sidebar_open = ref(false)
const user_menu_open = ref(false)
const user_menu_container = ref(null)

// Track which collapsible sections are expanded, keyed by label.
const open_sections = ref({})

function isSectionActive(section) {
    return section.children.some((child) => route().current(child.route))
}

function isSectionOpen(section) {
    // Auto-expand a section when one of its children is the current route.
    return open_sections.value[section.label] ?? isSectionActive(section)
}

function toggleSection(section) {
    open_sections.value = {
        ...open_sections.value,
        [section.label]: !isSectionOpen(section),
    }
}

// Theme preference (persisted per-user via the settings endpoint). Keep the
// applied `.dark` class in sync whenever the shared prop changes — e.g. after
// saving on the Settings page.
const current_theme = computed(() => page.props.theme ?? 'System')

const theme_options = computed(() => [
    { value: 'System', label: trans('settings.theme.system'), icon: Monitor },
    { value: 'Light', label: trans('settings.theme.light'), icon: Sun },
    { value: 'Dark', label: trans('settings.theme.dark'), icon: Moon },
])

watch(current_theme, (theme) => applyTheme(theme))

function setTheme(theme) {
    if (theme === current_theme.value) {
        return
    }

    // Apply immediately for instant feedback, then persist in the background.
    applyTheme(theme)
    router.put(route('settings.update'), { settings: { Theme: theme } }, {
        preserveScroll: true,
        preserveState: true,
    })
}

const auth_user = computed(() => page.props.auth?.user ?? null)

const user_initials = computed(() => {
    const first = auth_user.value?.first_name?.[0] ?? ''
    const last = auth_user.value?.last_name?.[0] ?? ''

    return `${first}${last}`.toUpperCase() || 'U'
})

function handleUserMenuClickOutside(event) {
    if (!user_menu_container.value?.contains(event.target)) {
        user_menu_open.value = false
    }
}

onMounted(() => document.addEventListener('click', handleUserMenuClickOutside))
onUnmounted(() => document.removeEventListener('click', handleUserMenuClickOutside))
</script>

<template>
    <Head :title="effective_title" />
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
                <span class="text-xl font-bold text-white">{{ $t('common.brand.name') }}</span>
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

                <!-- Collapsible sections -->
                <div v-for="section in nav_sections" :key="section.label">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 font-bold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                        :class="{ 'text-white': isSectionActive(section) }"
                        @click="toggleSection(section)"
                    >
                        <component :is="section.icon" class="size-4 shrink-0 text-white" />
                        <span class="flex-1 text-left">{{ section.label }}</span>
                        <component
                            :is="isSectionOpen(section) ? ChevronDown : ChevronUp"
                            class="size-4 shrink-0 text-white/60"
                        />
                    </button>
                    <div v-if="isSectionOpen(section)" class="mt-1 flex flex-col gap-1 pl-5">
                        <Link
                            v-for="child in section.children"
                            :key="child.route"
                            :href="route().has(child.route) ? route(child.route) : '#'"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                            :class="{ 'bg-white/15 text-white': route().current(child.route) }"
                            @click="sidebar_open = false"
                        >
                            <component :is="child.icon" class="size-4 shrink-0 text-white" />
                            <span>{{ child.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex h-16 items-center gap-4 border-b border-border bg-card px-6">
                <button
                    class="rounded p-1 text-foreground hover:text-primary lg:hidden"
                    @click="sidebar_open = true"
                >
                    <Menu class="size-5" />
                </button>
                <nav v-if="breadcrumbs.length" :aria-label="$t('common.a11y.breadcrumb')" class="flex items-center">
                    <template v-for="(crumb, index) in breadcrumbs" :key="index">
                        <span v-if="index > 0" aria-hidden="true" class="mx-1.5 text-lg text-muted-foreground">/</span>
                        <Link
                            v-if="crumb.href"
                            :href="crumb.href"
                            class="text-lg text-muted-foreground hover:text-foreground"
                        >{{ crumb.label }}</Link>
                        <span
                            v-else
                            aria-current="page"
                            class="text-lg font-bold text-foreground"
                        >{{ crumb.label }}</span>
                    </template>
                </nav>
                <h1 v-else class="text-lg font-bold text-foreground">{{ title }}</h1>

                <div class="ml-auto flex items-center gap-1">
                    <NotificationBell />

                    <!-- User menu -->
                    <div ref="user_menu_container" class="relative">
                        <!-- Trigger -->
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 text-left transition-colors hover:bg-muted/40"
                            @click="user_menu_open = !user_menu_open"
                        >
                            <img
                                :src="auth_user?.avatar_url"
                                :alt="user_initials"
                                class="size-8 shrink-0 rounded-full bg-muted object-cover"
                            />
                            <span class="hidden max-w-40 truncate text-sm font-bold text-foreground sm:block">
                                {{ auth_user?.first_name }} {{ auth_user?.last_name }}
                            </span>
                            <ChevronDown
                                class="size-4 shrink-0 text-muted-foreground transition-transform"
                                :class="{ 'rotate-180': user_menu_open }"
                            />
                        </button>

                        <!-- Menu -->
                        <div
                            v-if="user_menu_open"
                            class="absolute right-0 top-full z-30 mt-2 w-64 overflow-hidden rounded-lg border border-border bg-popover py-1 shadow-lg"
                        >
                            <div class="border-b border-border px-3 py-2">
                                <p class="truncate text-sm font-bold text-foreground">
                                    {{ auth_user?.first_name }} {{ auth_user?.last_name }}
                                </p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ auth_user?.email }}
                                </p>
                            </div>

                            <!-- Theme selector -->
                            <div class="px-3 py-2">
                                <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                    {{ $t('settings.theme.label') }}
                                </p>
                                <div class="grid grid-cols-3 gap-1">
                                    <button
                                        v-for="option in theme_options"
                                        :key="option.value"
                                        type="button"
                                        class="flex flex-col items-center gap-1 rounded-md border px-2 py-1.5 text-xs font-bold transition-colors"
                                        :class="current_theme === option.value
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'border-border text-muted-foreground hover:bg-muted/40'"
                                        :aria-pressed="current_theme === option.value"
                                        @click="setTheme(option.value)"
                                    >
                                        <component :is="option.icon" class="size-4 shrink-0" />
                                        <span>{{ option.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="my-1 border-t border-border" />

                            <Link
                                v-if="route().has('settings.index')"
                                :href="route('settings.index')"
                                class="flex items-center gap-3 px-3 py-2.5 text-sm font-bold text-foreground transition-colors hover:bg-muted/40"
                                :class="{ 'text-primary': route().current('settings.index') }"
                                @click="user_menu_open = false"
                            >
                                <Settings class="size-4 shrink-0" />
                                <span>{{ $t('nav.settings') }}</span>
                            </Link>
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-3 py-2.5 text-sm font-bold text-foreground transition-colors hover:bg-muted/40"
                                @click="router.post(route('logout'))"
                            >
                                <LogOut class="size-4 shrink-0" />
                                <span>{{ $t('common.labels.sign_out') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto bg-background p-6">
                <slot />
                <ErrorModal />
            </main>
        </div>
        <Toaster />
    </div>
</template>
