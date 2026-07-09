<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const page = usePage()

const open = ref(false)
const container = ref(null)

// Server truth (refreshed on every Inertia navigation) plus any items that
// arrive live over the broadcast channel between navigations.
const items = ref([...(page.props.notifications?.items ?? [])])
const unread_count = ref(page.props.notifications?.unread_count ?? 0)

watch(() => page.props.notifications, (incoming) => {
    items.value = [...(incoming?.items ?? [])]
    unread_count.value = incoming?.unread_count ?? 0
})

const user_id = computed(() => page.props.auth?.user?.id ?? null)

function openRoute(id) {
    return route('notifications.open', id)
}

function markReadLocally(id) {
    const item = items.value.find((notification) => notification.id === id)

    if (item && !item.read_at) {
        item.read_at = new Date().toISOString()
        unread_count.value = Math.max(0, unread_count.value - 1)
    }

    open.value = false
}

function markAllRead() {
    items.value.forEach((notification) => { notification.read_at = notification.read_at ?? new Date().toISOString() })
    unread_count.value = 0

    router.post(route('notifications.read-all'), {}, { preserveScroll: true, preserveState: true })
}

function handleClickOutside(event) {
    if (!container.value?.contains(event.target)) {
        open.value = false
    }
}

let channel_name = null

onMounted(() => {
    document.addEventListener('click', handleClickOutside)

    if (user_id.value && window.Echo) {
        channel_name = `App.Models.User.${user_id.value}`
        window.Echo.private(channel_name).notification((incoming) => {
            items.value = [
                {
                    id: incoming.id,
                    title: incoming.title,
                    body: incoming.body,
                    read_at: null,
                    created_at: new Date().toISOString(),
                },
                ...items.value,
            ]
            unread_count.value += 1
        })
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)

    if (channel_name && window.Echo) {
        window.Echo.leave(channel_name)
    }
})
</script>

<template>
    <div ref="container" class="relative">
        <button
            type="button"
            class="relative rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted/40 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
            :aria-label="$t('notifications.aria_label')"
            @click="open = !open"
        >
            <Bell class="size-5" />
            <span
                v-if="unread_count > 0"
                class="absolute -right-0.5 -top-0.5 flex min-w-[1.125rem] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-4 text-primary-foreground"
            >
                {{ unread_count > 9 ? '9+' : unread_count }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-30 mt-2 w-80 overflow-hidden rounded-xl border border-border bg-popover shadow-lg"
        >
            <div class="flex items-center justify-between border-b border-border px-4 py-2.5">
                <span class="text-sm font-bold text-foreground">{{ $t('notifications.heading') }}</span>
                <button
                    v-if="unread_count > 0"
                    type="button"
                    class="text-xs font-bold text-primary hover:underline"
                    @click="markAllRead"
                >
                    {{ $t('notifications.mark_all_read') }}
                </button>
            </div>

            <p v-if="items.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                {{ $t('notifications.empty') }}
            </p>

            <ul v-else class="max-h-96 divide-y divide-border overflow-y-auto">
                <li v-for="notification in items" :key="notification.id">
                    <Link
                        :href="openRoute(notification.id)"
                        class="flex gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
                        :class="{ 'bg-primary/5': !notification.read_at }"
                        @click="markReadLocally(notification.id)"
                    >
                        <span
                            class="mt-1.5 size-2 shrink-0 rounded-full"
                            :class="notification.read_at ? 'bg-transparent' : 'bg-primary'"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold text-foreground">{{ notification.title }}</span>
                            <span class="mt-0.5 block truncate text-xs text-muted-foreground">{{ notification.body }}</span>
                            <span class="mt-1 block text-[11px] text-muted-foreground">{{ formatDate(notification.created_at, DATE_SHORT) }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </div>
    </div>
</template>
