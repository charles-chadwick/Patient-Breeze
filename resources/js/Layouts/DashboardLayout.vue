<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, HeartPulse, CalendarDays, Users, Settings, Menu, X, LogOut } from 'lucide-vue-next'

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
                <nav v-if="breadcrumbs.length" aria-label="Breadcrumb" class="flex items-center">
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
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto bg-background p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
