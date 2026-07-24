<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { CalendarDays, Inbox } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({ title: computed(() => trans('nav.dashboard')) })

const props = defineProps({
    stats: { type: Object, required: true },
    portal_queue: { type: Array, required: true },
    todays_appointments: { type: Array, default: () => [] },
    can_view_appointments: { type: Boolean, default: false },
})

// Mirrors the status pill palette used on the Appointments page.
const status_classes = {
    Scheduled: 'bg-cerulean-100 text-cerulean-700',
    Confirmed: 'bg-tropical-teal-100 text-tropical-teal-700',
    Completed: 'bg-muted text-muted-foreground',
    Cancelled: 'bg-vibrant-coral-100 text-vibrant-coral-700',
    Rescheduled: 'bg-light-yellow-100 text-light-yellow-700',
    NoShow: 'bg-soft-apricot-100 text-soft-apricot-700',
}

function formatTime (time) {
    return time?.slice(0, 5) ?? ''
}

const has_appointments = computed(() => props.todays_appointments.length > 0)

// Computed so labels re-evaluate once the async language file has loaded.
const stat_cards = computed(() => [
    { label: trans('dashboard.stats.total_patients'), key: 'total_patients' },
    { label: trans('dashboard.stats.appointments_today'), key: 'appointments_today' },
    { label: trans('dashboard.stats.pending_appointments'), key: 'pending_reviews' },
    { label: trans('dashboard.stats.portal_queue_unread'), key: 'portal_queue_unread' },
])

const live_queue = ref([...props.portal_queue])
const live_unread = ref(props.stats.portal_queue_unread)

const has_items = computed(() => live_queue.value.length > 0)

let channel = null

onMounted(() => {
    if (!window.Echo) {
        return
    }
    channel = window.Echo.private('portal-queue')
    channel.listen('.PortalNotificationCreated', (payload) => {
        if (!payload?.notification) {
            return
        }
        if (live_queue.value.some((n) => n.id === payload.notification.id)) {
            return
        }
        live_queue.value.unshift(payload.notification)
        if (live_queue.value.length > 5) {
            live_queue.value.pop()
        }
        if (!payload.notification.read_at) {
            live_unread.value += 1
        }
    })
})

onBeforeUnmount(() => {
    if (window.Echo) {
        window.Echo.leave('private-portal-queue')
    }
})
</script>

<template>
    <div class="grid gap-6">
        <!-- Stats row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in stat_cards"
                :key="card.key"
                class="rounded-xl border border-border bg-card p-5 shadow-sm"
            >
                <p class="text-sm font-bold text-muted-foreground">{{ card.label }}</p>
                <p class="mt-1 text-3xl font-bold text-foreground">
                    {{ card.key === 'portal_queue_unread' ? live_unread : props.stats[card.key] }}
                </p>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div v-if="can_view_appointments" class="rounded-xl border border-border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <div class="flex items-center gap-2">
                    <CalendarDays class="size-5 text-primary" />
                    <h2 class="text-base font-bold text-foreground">{{ $t('dashboard.todays_appointments.heading') }}</h2>
                </div>
                <Link
                    :href="route('appointments.index', { view: 'day' })"
                    class="text-sm font-semibold text-primary hover:underline"
                >
                    {{ $t('dashboard.todays_appointments.view_all') }}
                </Link>
            </div>
            <p v-if="!has_appointments" class="px-6 py-8 text-sm text-muted-foreground">
                {{ $t('dashboard.todays_appointments.empty') }}
            </p>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="appointment in props.todays_appointments"
                    :key="appointment.id"
                    class="flex items-center gap-3 px-6 py-3"
                >
                    <img
                        :src="appointment.patient.avatar_url"
                        :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                        class="size-9 shrink-0 rounded-full object-cover"
                    />
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="route('patients.show', appointment.patient.id)"
                            class="text-sm font-semibold text-foreground hover:text-primary hover:underline"
                        >
                            {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                        </Link>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ formatTime(appointment.start_time) }}–{{ formatTime(appointment.end_time) }}
                            <span v-if="appointment.reason">· {{ appointment.reason }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            <template v-if="appointment.users.length">
                                {{ appointment.users.map((user) => `${user.first_name} ${user.last_name}`).join(', ') }}
                            </template>
                            <template v-else>{{ $t('dashboard.todays_appointments.no_staff') }}</template>
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="status_classes[appointment.status] ?? 'bg-muted text-muted-foreground'"
                    >
                        {{ $t('enums.appointment_status.' + appointment.status) }}
                    </span>
                </li>
            </ul>
        </div>

        <!-- Portal Queue -->
        <div class="rounded-xl border border-border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <div class="flex items-center gap-2">
                    <Inbox class="size-5 text-primary" />
                    <h2 class="text-base font-bold text-foreground">{{ $t('dashboard.portal_queue.heading') }}</h2>
                    <span
                        v-if="live_unread > 0"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary"
                    >
                        {{ $t('dashboard.portal_queue.unread_badge', { count: live_unread }) }}
                    </span>
                </div>
                <Link :href="route('portal-queue.index')" class="text-sm font-semibold text-primary hover:underline">
                    {{ $t('dashboard.portal_queue.view_all') }}
                </Link>
            </div>
            <p v-if="!has_items" class="px-6 py-8 text-sm text-muted-foreground">
                {{ $t('dashboard.portal_queue.empty') }}
            </p>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="notification in live_queue"
                    :key="notification.id"
                    class="flex items-start gap-3 px-6 py-3"
                    :class="{ 'bg-primary/5': !notification.read_at }"
                >
                    <span
                        v-if="!notification.read_at"
                        class="mt-1.5 inline-block size-2 shrink-0 rounded-full bg-primary"
                        aria-hidden="true"
                    />
                    <span v-else class="mt-1.5 inline-block size-2 shrink-0" aria-hidden="true" />
                    <div class="flex-1">
                        <Link
                            v-if="notification.url"
                            :href="notification.url"
                            class="text-sm font-semibold text-foreground hover:text-primary hover:underline"
                        >
                            {{ notification.title }}
                        </Link>
                        <p v-else class="text-sm font-semibold text-foreground">{{ notification.title }}</p>
                        <p v-if="notification.body" class="mt-0.5 text-sm text-muted-foreground">{{ notification.body }}</p>
                        <div class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                            <Link
                                v-if="notification.patient"
                                :href="route('patients.show', notification.patient.id)"
                                class="font-medium hover:text-primary"
                            >
                                {{ notification.patient.first_name }} {{ notification.patient.last_name }}
                                <span>· {{ notification.patient.mrn }}</span>
                            </Link>
                            <span>{{ formatDate(notification.created_at, DATE_SHORT) }}</span>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
