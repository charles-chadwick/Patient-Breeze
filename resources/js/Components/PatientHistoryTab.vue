<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import ActivityChanges from '@/Components/ActivityChanges.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    history: {
        type: Object,
        default: null,
    },
})

const expanded = ref(new Set())

function toggle(id) {
    const next = new Set(expanded.value)
    next.has(id) ? next.delete(id) : next.add(id)
    expanded.value = next
}

function actionLabel(event) {
    return event ? trans('audit.actions.' + event) : event
}

function subjectLabel(activity) {
    return activity.subject_key ? trans('audit.subjects.' + activity.subject_key) : activity.subject_type
}

function goToPage(url) {
    if (!url) {
        return
    }

    const page = new URL(url, window.location.origin).searchParams.get('history_page')
    router.reload({ only: ['history'], data: { history_page: page }, preserveScroll: true })
}
</script>

<template>
    <div class="px-6 py-5">
        <!-- Skeleton while the deferred prop loads -->
        <div v-if="history === null" class="space-y-3">
            <div v-for="i in 4" :key="i" class="flex items-center gap-4">
                <div class="size-8 animate-pulse rounded-full bg-muted"></div>
                <div class="h-4 w-64 animate-pulse rounded bg-muted"></div>
            </div>
        </div>

        <div v-else-if="history.data.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            {{ $t('audit.tab.empty') }}
        </div>

        <div v-else>
            <ul class="space-y-2">
                <li v-for="activity in history.data" :key="activity.id" class="rounded-lg border border-border">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-muted/40"
                        @click="toggle(activity.id)"
                    >
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">{{ actionLabel(activity.event) }}</span>
                        <span class="font-medium text-foreground">{{ subjectLabel(activity) }}</span>
                        <span class="text-sm text-muted-foreground">{{ activity.causer_name ?? $t('audit.system_user') }}</span>
                        <span class="ml-auto text-xs text-muted-foreground">{{ formatDate(activity.created_at, DATE_SHORT) }}</span>
                    </button>
                    <div v-if="expanded.has(activity.id)" class="border-t border-border px-4 py-3">
                        <ActivityChanges :changes="activity.changes" />
                    </div>
                </li>
            </ul>

            <div v-if="history.total > history.per_page" class="mt-4 flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ $t('common.pagination.summary', { from: history.from, to: history.to, total: history.total, label: $t('audit.index.record_label') }) }}
                </p>
                <div class="flex items-center gap-1">
                    <button
                        v-if="history.prev_page_url"
                        type="button"
                        @click="goToPage(history.prev_page_url)"
                        class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                    >←</button>
                    <button
                        v-if="history.next_page_url"
                        type="button"
                        @click="goToPage(history.next_page_url)"
                        class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                    >→</button>
                </div>
            </div>
        </div>
    </div>
</template>
