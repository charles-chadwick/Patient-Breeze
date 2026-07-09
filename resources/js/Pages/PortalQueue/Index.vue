<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Inbox, Check, CalendarClock, X as XIcon } from 'lucide-vue-next'
import { formatDate, DATE_SHORT, DATE_LONG } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [{ label: trans('nav.portal_queue') }]),
})

const props = defineProps({
    notifications: { type: Array, required: true },
    unread_count: { type: Number, required: true },
    appointment_requests: { type: Array, default: () => [] },
})

const reviewing_id = ref(null)

function reviewRequest(request, action) {
    if (reviewing_id.value) {
        return
    }
    reviewing_id.value = request.id
    router.post(route(`portal-queue.appointment-requests.${action}`, request.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            reviewing_id.value = null
        },
    })
}

const live_notifications = ref([...props.notifications])

const unread_total = computed(() =>
    live_notifications.value.filter((n) => !n.read_at).length
)

function markRead(notification) {
    if (notification.read_at) {
        return
    }
    notification.read_at = new Date().toISOString()
    router.post(route('portal-queue.read', notification.id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

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
        const exists = live_notifications.value.some((n) => n.id === payload.notification.id)
        if (!exists) {
            live_notifications.value.unshift(payload.notification)
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
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Inbox class="size-6 text-primary" />
                <h1 class="text-2xl font-bold text-foreground">{{ $t('nav.portal_queue') }}</h1>
                <span
                    v-if="unread_total > 0"
                    class="rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary"
                >
                    {{ $t('portal_queue.unread_badge', { count: unread_total }) }}
                </span>
            </div>
        </div>

        <!-- Pending appointment requests -->
        <div v-if="appointment_requests.length" class="rounded-2xl border border-border bg-card shadow-sm">
            <div class="flex items-center gap-3 border-b border-border px-5 py-4">
                <CalendarClock class="size-5 text-primary" />
                <h2 class="text-base font-semibold text-foreground">{{ $t('portal_queue.requests.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary">
                    {{ $t('portal_queue.requests.badge', { count: appointment_requests.length }) }}
                </span>
            </div>
            <ul class="divide-y divide-border">
                <li
                    v-for="request in appointment_requests"
                    :key="request.id"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 text-sm font-semibold text-foreground">
                            <Link
                                v-if="request.patient"
                                :href="route('patients.show', request.patient.id)"
                                class="hover:text-primary hover:underline"
                            >
                                {{ request.patient.first_name }} {{ request.patient.last_name }}
                            </Link>
                            <span v-if="request.patient" class="text-xs font-normal text-muted-foreground">· {{ request.patient.mrn }}</span>
                        </div>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ formatDate(request.date, DATE_LONG) }} · {{ request.start_time }} – {{ request.end_time }}
                            <span v-if="request.provider">
                                {{ $t('portal_queue.requests.with_provider', { name: `${request.provider.first_name} ${request.provider.last_name}` }) }}
                            </span>
                        </p>
                        <p class="mt-0.5 text-sm text-foreground">
                            <span class="text-muted-foreground">{{ $t('portal_queue.requests.reason') }}:</span> {{ request.reason }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            type="button"
                            :disabled="reviewing_id === request.id"
                            @click="reviewRequest(request, 'approve')"
                            class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-60"
                        >
                            <Check class="size-4" />
                            {{ reviewing_id === request.id ? $t('portal_queue.requests.approving') : $t('portal_queue.requests.approve') }}
                        </button>
                        <button
                            type="button"
                            :disabled="reviewing_id === request.id"
                            @click="reviewRequest(request, 'decline')"
                            class="inline-flex items-center gap-1 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-muted-foreground hover:bg-muted disabled:opacity-60"
                        >
                            <XIcon class="size-4" />
                            {{ $t('portal_queue.requests.decline') }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <div class="rounded-2xl border border-border bg-card shadow-sm">
            <div v-if="live_notifications.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                {{ $t('portal_queue.empty') }}
            </div>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="notification in live_notifications"
                    :key="notification.id"
                    class="flex items-start justify-between gap-4 p-4 transition-colors"
                    :class="{ 'bg-primary/5': !notification.read_at }"
                >
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                v-if="!notification.read_at"
                                class="inline-block size-2 rounded-full bg-primary"
                                aria-hidden="true"
                            />
                            <Link
                                v-if="notification.url"
                                :href="notification.url"
                                class="text-sm font-semibold text-foreground hover:text-primary hover:underline"
                                @click="markRead(notification)"
                            >
                                {{ notification.title }}
                            </Link>
                            <p v-else class="text-sm font-semibold text-foreground">{{ notification.title }}</p>
                        </div>
                        <p v-if="notification.body" class="mt-1 text-sm text-muted-foreground">{{ notification.body }}</p>
                        <div class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                            <Link
                                v-if="notification.patient"
                                :href="route('patients.show', notification.patient.id)"
                                class="font-medium hover:text-primary"
                            >
                                {{ notification.patient.first_name }} {{ notification.patient.last_name }}
                                <span class="text-muted-foreground">· {{ notification.patient.mrn }}</span>
                            </Link>
                            <span>{{ formatDate(notification.created_at, DATE_SHORT) }}</span>
                        </div>
                    </div>
                    <button
                        v-if="!notification.read_at"
                        class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10"
                        @click="markRead(notification)"
                    >
                        <Check class="size-4" /> {{ $t('portal_queue.mark_read') }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
