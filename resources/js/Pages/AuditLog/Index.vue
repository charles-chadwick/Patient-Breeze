<script setup>
import { computed, ref } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ActivityChanges from '@/Components/ActivityChanges.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.audit_log') },
    ]),
})

const props = defineProps({
    activities: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    causer_options: {
        type: Array,
        default: () => [],
    },
    subject_options: {
        type: Array,
        default: () => [],
    },
    event_options: {
        type: Array,
        default: () => [],
    },
    patient: {
        type: Object,
        default: null,
    },
})

const form = ref({
    causer_id: props.filters.causer_id ?? '',
    subject_type: props.filters.subject_type ?? '',
    event: props.filters.event ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

// The active filters as query params, shared by the list request and the
// PDF export link so both reflect exactly what the user is viewing.
function currentParams() {
    const params = {}
    for (const [key, value] of Object.entries(form.value)) {
        if (value !== '' && value !== null) {
            params[key] = value
        }
    }

    // Keep the patient scope while adjusting the other filters.
    if (props.filters.patient_id) {
        params.patient_id = props.filters.patient_id
    }

    return params
}

function applyFilters() {
    router.get(route('audit-log.index'), currentParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const export_url = computed(() => route('audit-log.export', currentParams()))

function resetFilters() {
    form.value = { causer_id: '', subject_type: '', event: '', date_from: '', date_to: '' }
    applyFilters()
}

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

const select_class = 'w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/50'
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="border-b border-border px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-foreground">{{ $t('audit.index.heading') }}</h2>
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">{{ activities.total }}</span>
                </div>
                <a
                    :href="export_url"
                    data-testid="audit-log-export"
                    class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('audit.index.export') }}
                </a>
            </div>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ $t('audit.index.subheading') }}</p>
            <div v-if="patient" class="mt-3 flex flex-wrap items-center gap-3 rounded-lg bg-primary/5 px-3 py-2">
                <span class="text-sm font-bold text-foreground">
                    {{ $t('audit.index.scoped_to_patient', { name: patient.name }) }}
                </span>
                <Link
                    :href="route('audit-log.index')"
                    class="text-sm font-bold text-primary hover:underline"
                >
                    {{ $t('audit.index.view_all') }}
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid gap-3 border-b border-border px-6 py-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.index.filter_causer') }}</label>
                <select v-model="form.causer_id" :class="select_class" @change="applyFilters">
                    <option value="">{{ $t('audit.index.filter_all') }}</option>
                    <option v-for="opt in causer_options" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                </select>
            </div>
            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.index.filter_subject') }}</label>
                <select v-model="form.subject_type" :class="select_class" @change="applyFilters">
                    <option value="">{{ $t('audit.index.filter_all') }}</option>
                    <option v-for="opt in subject_options" :key="opt.value" :value="opt.value">{{ $t('audit.subjects.' + opt.key) }}</option>
                </select>
            </div>
            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.index.filter_event') }}</label>
                <select v-model="form.event" :class="select_class" @change="applyFilters">
                    <option value="">{{ $t('audit.index.filter_all') }}</option>
                    <option v-for="opt in event_options" :key="opt" :value="opt">{{ $t('audit.actions.' + opt) }}</option>
                </select>
            </div>
            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.index.filter_date_from') }}</label>
                <input v-model="form.date_from" type="date" :class="select_class" @change="applyFilters" />
            </div>
            <div class="lg:col-span-1">
                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('audit.index.filter_date_to') }}</label>
                <input v-model="form.date_to" type="date" :class="select_class" @change="applyFilters" />
            </div>
            <div class="flex items-end lg:col-span-1">
                <button type="button" @click="resetFilters" class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40">
                    {{ $t('audit.index.reset_filters') }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('audit.index.column_causer') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('audit.index.column_action') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('audit.index.column_subject') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('audit.index.column_when') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="activities.data.length === 0">
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-muted-foreground">{{ $t('audit.index.empty') }}</td>
                    </tr>
                    <template v-for="activity in activities.data" :key="activity.id">
                        <tr class="cursor-pointer hover:bg-muted/40" @click="toggle(activity.id)">
                            <td class="px-6 py-4 font-bold text-foreground">{{ activity.causer_name ?? $t('audit.system_user') }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">{{ actionLabel(activity.event) }}</span>
                            </td>
                            <td class="px-6 py-4 text-foreground">
                                {{ subjectLabel(activity) }}
                                <span class="text-muted-foreground">#{{ activity.subject_id }}</span>
                            </td>
                            <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ formatDate(activity.created_at, DATE_SHORT) }}</td>
                            <td class="px-6 py-4 text-right text-xs font-bold text-muted-foreground">
                                {{ expanded.has(activity.id) ? '▲' : '▼' }}
                            </td>
                        </tr>
                        <tr v-if="expanded.has(activity.id)" class="bg-muted/10">
                            <td colspan="5" class="px-6 py-4">
                                <ActivityChanges :changes="activity.changes" />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div v-if="activities.total > 0" class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('common.pagination.summary', { from: activities.from, to: activities.to, total: activities.total, label: $t('audit.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link v-if="activities.prev_page_url" :href="activities.prev_page_url" preserve-scroll class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40">←</Link>
                <template v-for="link in activities.links.slice(1, -1)" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
                        class="rounded-lg border px-3 py-1.5 text-sm font-bold"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'border-border text-foreground hover:bg-muted/40'"
                    >{{ link.label }}</Link>
                    <span v-else class="px-2 py-1.5 text-sm text-muted-foreground">{{ link.label }}</span>
                </template>
                <Link v-if="activities.next_page_url" :href="activities.next_page_url" preserve-scroll class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40">→</Link>
            </div>
        </div>
    </div>
</template>
