<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, HeartPulse, CalendarDays, Users, Settings } from 'lucide-vue-next'

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
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
</script>

<template>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="flex w-64 flex-col bg-primary">
            <!-- Logo -->
            <div class="flex h-16 items-center px-6">
                <span class="text-xl font-bold text-white">PB Health</span>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-1 flex-col gap-1 px-3 py-4">
                <Link
                    v-for="item in nav_items"
                    :key="item.route"
                    :href="route().has(item.route) ? route(item.route) : '#'"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                    :class="{ 'bg-white/15 text-white': route().current(item.route) }"
                >
                    <component :is="item.icon" class="size-4 shrink-0 text-white" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <!-- User footer -->
            <div class="border-t border-white/20 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-sm font-bold text-white">
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
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex h-16 items-center border-b border-border bg-white px-6">
                <h1 class="text-lg font-bold text-foreground">{{ props.title }}</h1>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto bg-background p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
