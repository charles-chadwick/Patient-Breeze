<script setup>
import { computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MiniCalendar from '@/Components/ui/MiniCalendar.vue'
import MultiSelect from '@/Components/ui/MultiSelect.vue'
import SearchInput from '@/Components/SearchInput.vue'
import { cn, formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({ title: 'Appointments' })

const props = defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
    date: {
        type: String,
        required: true,
    },
    view: {
        type: String,
        default: 'week',
    },
    search: {
        type: String,
        default: '',
    },
    staff: {
        type: Array,
        default: () => [],
    },
    staff_options: {
        type: Array,
        default: () => [],
    },
})

const staffSelectOptions = computed(() =>
    props.staff_options.map((u) => ({ value: u.id, label: `${u.last_name}, ${u.first_name}`, avatar: u.avatar_url })),
)

const statusClasses = {
    Scheduled: 'bg-blue-100 text-blue-700',
    Confirmed: 'bg-green-100 text-green-700',
    Completed: 'bg-gray-100 text-gray-600',
    Cancelled: 'bg-red-100 text-red-700',
    Rescheduled: 'bg-yellow-100 text-yellow-700',
    NoShow: 'bg-orange-100 text-orange-700',
}

const appointmentsByDate = computed(() => {
    const groups = {}
    for (const appointment of props.appointments) {
        const key = appointment.date.substring(0, 10)
        if (!groups[key]) groups[key] = []
        groups[key].push(appointment)
    }
    return Object.keys(groups)
        .sort()
        .map((date) => ({ date, items: groups[date] }))
})

function formatTime(time) {
    return time?.slice(0, 5) ?? ''
}

function navigate(overrides = {}) {
    router.get(
        route('appointments.index'),
        {
            date: props.date,
            view: props.view,
            search: props.search || undefined,
            staff: props.staff.length ? props.staff : undefined,
            ...overrides,
        },
        { preserveState: true, replace: true },
    )
}

function onDateChange(newDate) {
    navigate({ date: newDate })
}

function setView(newView) {
    navigate({ view: newView })
}

function onStaffChange(newStaff) {
    navigate({ staff: newStaff.length ? newStaff : undefined })
}
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <!-- Left: mini calendar + view toggle -->
        <aside class="w-full shrink-0 lg:w-1/3">
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <MiniCalendar
                    :model-value="date"
                    :view="view"
                    @update:model-value="onDateChange"
                />

                <div class="mt-4 flex rounded-lg border border-border p-1 gap-1">
                    <button
                        type="button"
                        :class="cn(
                            'flex-1 rounded-md py-1.5 text-sm font-bold transition-colors focus:outline-none',
                            view === 'day' ? 'bg-primary text-white' : 'text-muted-foreground hover:text-foreground',
                        )"
                        @click="setView('day')"
                    >
                        Day
                    </button>
                    <button
                        type="button"
                        :class="cn(
                            'flex-1 rounded-md py-1.5 text-sm font-bold transition-colors focus:outline-none',
                            view === 'week' ? 'bg-primary text-white' : 'text-muted-foreground hover:text-foreground',
                        )"
                        @click="setView('week')"
                    >
                        Week
                    </button>
                </div>
            </div>
        </aside>

        <!-- Right: filters + appointment list -->
        <div class="min-w-0 flex-1">
            <!-- Filters -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <SearchInput
                    :model-value="search"
                    placeholder="Search by patient name…"
                    route-name="appointments.index"
                    :params="{
                        date,
                        view,
                        staff: staff.length ? staff : undefined,
                    }"
                    class="w-full sm:max-w-xs"
                />
                <MultiSelect
                    :model-value="staff"
                    :options="staffSelectOptions"
                    placeholder="Filter by staff…"
                    class="w-full sm:max-w-xs"
                    @update:model-value="onStaffChange"
                />
            </div>

            <!-- Empty state -->
            <div
                v-if="appointmentsByDate.length === 0"
                class="rounded-xl border border-border bg-white px-6 py-14 text-center shadow-sm"
            >
                <p class="text-sm text-muted-foreground">No appointments found for this period.</p>
            </div>

            <!-- Grouped appointment list -->
            <div v-else class="flex flex-col gap-6">
                <section v-for="group in appointmentsByDate" :key="group.date">
                    <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ formatDate(group.date, DATE_SHORT) }}
                    </h2>

                    <div class="flex flex-col gap-2">
                        <PopoverRoot
                            v-for="appointment in group.items"
                            :key="appointment.id"
                        >
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    class="w-full rounded-xl border border-border bg-white px-5 py-4 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary/50"
                                >
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                            <img
                                                :src="appointment.patient.avatar_url"
                                                :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                                                class="size-9 shrink-0 rounded-full object-cover"
                                            />
                                            <div class="min-w-0">
                                                <p class="font-bold text-foreground">
                                                    {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                                </p>
                                                <p class="mt-0.5 text-sm text-muted-foreground">
                                                    {{ formatTime(appointment.start_time) }}–{{ formatTime(appointment.end_time) }}
                                                </p>
                                                <p v-if="appointment.users?.length" class="mt-0.5 text-sm text-muted-foreground">
                                                    {{ appointment.users.map((u) => `${u.first_name} ${u.last_name}`).join(', ') }}
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold"
                                            :class="statusClasses[appointment.status] ?? 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ appointment.status }}
                                        </span>
                                    </div>
                                </button>
                            </PopoverTrigger>

                            <PopoverPortal>
                                <PopoverContent
                                    side="bottom"
                                    align="start"
                                    :side-offset="8"
                                    :collision-padding="16"
                                    class="z-50 w-80 rounded-xl border border-border bg-white p-5 shadow-xl focus:outline-none"
                                >
                                    <div class="mb-4 flex items-center gap-3 border-b border-border pb-4">
                                        <img
                                            :src="appointment.patient.avatar_url"
                                            :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                                            class="size-10 rounded-full object-cover"
                                        />
                                        <Link
                                            :href="route('patients.show', appointment.patient.id)"
                                            class="text-base font-bold text-primary hover:underline"
                                        >
                                            {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                                        </Link>
                                    </div>

                                    <dl class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between gap-2">
                                            <dt class="font-bold text-muted-foreground">Date</dt>
                                            <dd class="text-foreground">{{ formatDate(appointment.date, DATE_SHORT) }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <dt class="font-bold text-muted-foreground">Time</dt>
                                            <dd class="text-foreground">
                                                {{ formatTime(appointment.start_time) }}–{{ formatTime(appointment.end_time) }}
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <dt class="font-bold text-muted-foreground">Status</dt>
                                            <dd>
                                                <span
                                                    class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                    :class="statusClasses[appointment.status] ?? 'bg-gray-100 text-gray-600'"
                                                >
                                                    {{ appointment.status }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div v-if="appointment.reason" class="flex items-start justify-between gap-2">
                                            <dt class="font-bold text-muted-foreground">Reason</dt>
                                            <dd class="text-right text-foreground">{{ appointment.reason }}</dd>
                                        </div>
                                    </dl>

                                    <div v-if="appointment.users?.length" class="mt-4 border-t border-border pt-4">
                                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">Staff</p>
                                        <ul class="space-y-1.5">
                                            <li
                                                v-for="user in appointment.users"
                                                :key="user.id"
                                                class="flex items-center gap-2 text-sm"
                                            >
                                                <img
                                                    :src="user.avatar_url"
                                                    :alt="`${user.first_name} ${user.last_name}`"
                                                    class="size-7 rounded-full object-cover"
                                                />
                                                <span class="flex-1 text-foreground">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                                <span class="rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                    {{ user.pivot.role }}
                                                </span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mt-4 border-t border-border pt-4">
                                        <Link
                                            :href="route('patients.appointments.edit', [appointment.patient.id, appointment.id])"
                                            class="text-sm font-bold text-primary hover:underline"
                                        >
                                            Edit Appointment →
                                        </Link>
                                    </div>
                                </PopoverContent>
                            </PopoverPortal>
                        </PopoverRoot>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
