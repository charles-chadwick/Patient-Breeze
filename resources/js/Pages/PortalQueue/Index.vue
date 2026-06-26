<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Inbox, Check } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: [{ label: 'Portal Queue' }],
})

const props = defineProps({
    notifications: { type: Array, required: true },
    unread_count: { type: Number, required: true },
})

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
                <h1 class="text-2xl font-bold text-foreground">Portal Queue</h1>
                <span
                    v-if="unread_total > 0"
                    class="rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary"
                >
                    {{ unread_total }} unread
                </span>
            </div>
        </div>

        <div class="rounded-2xl border border-border bg-white shadow-sm">
            <div v-if="live_notifications.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                No portal activity yet. Notifications will appear here in real time.
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
                        <Check class="size-4" /> Mark read
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
