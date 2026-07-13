<script setup>
import { computed } from 'vue'
import { Link, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions({ layout: DashboardLayout })

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.encounter_notes') },
    ]),
})

const props = defineProps({
    notes: {
        type: Object,
        required: true,
    },
})

function patientName(note) {
    return note.patient
        ? `${note.patient.first_name} ${note.patient.last_name}`
        : trans('common.placeholders.em_dash')
}

function signerName(note) {
    return note.signer
        ? `${note.signer.first_name} ${note.signer.last_name}`
        : trans('common.placeholders.em_dash')
}
</script>

<template>
    <div class="rounded border border-border bg-card shadow-sm">
        <div class="flex flex-col gap-1 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">{{ $t('encounter_notes.index.heading') }}</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ notes.total }}
                </span>
            </div>
            <p class="text-sm text-muted-foreground">{{ $t('encounter_notes.index.subheading') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.index.column_patient') }}</th>
                        <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.index.column_note') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground sm:table-cell">{{ $t('encounter_notes.index.column_signed_by') }}</th>
                        <th class="hidden px-6 py-3 font-bold text-muted-foreground md:table-cell">{{ $t('encounter_notes.index.column_signed_at') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-if="notes.data.length === 0">
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-muted-foreground">
                            {{ $t('encounter_notes.index.empty') }}
                        </td>
                    </tr>
                    <tr v-for="note in notes.data" :key="note.id" class="hover:bg-muted/40">
                        <td class="px-6 py-4 font-bold text-foreground">{{ patientName(note) }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-foreground">{{ note.title }}</div>
                            <div class="text-xs text-muted-foreground">{{ $t('enums.encounter_note_type.' + note.type) }}</div>
                        </td>
                        <td class="hidden px-6 py-4 text-muted-foreground sm:table-cell">{{ signerName(note) }}</td>
                        <td class="hidden px-6 py-4 text-muted-foreground md:table-cell">{{ formatDate(note.signed_at, DATE_SHORT) }}</td>
                        <td class="px-6 py-4 text-right">
                            <Link
                                as="button"
                                type="button"
                                :href="route('patients.show', note.patient_id)"
                                class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                            >
                                {{ $t('encounter_notes.index.open') }}
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="notes.total > 0" class="flex items-center justify-between border-t border-border px-6 py-4">
            <p class="text-sm text-muted-foreground">
                {{ $t('common.pagination.summary', { from: notes.from, to: notes.to, total: notes.total, label: $t('encounter_notes.index.record_label') }) }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="notes.prev_page_url"
                    :href="notes.prev_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    ←
                </Link>
                <template v-for="link in notes.links.slice(1, -1)" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm font-bold"
                        :class="link.active
                            ? 'border-primary bg-primary text-white'
                            : 'border-border text-foreground hover:bg-muted/40'"
                    >
                        {{ link.label }}
                    </Link>
                    <span v-else class="px-2 py-1.5 text-sm text-muted-foreground">{{ link.label }}</span>
                </template>
                <Link
                    v-if="notes.next_page_url"
                    :href="notes.next_page_url"
                    class="rounded-lg border border-border px-3 py-1.5 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    →
                </Link>
            </div>
        </div>
    </div>
</template>
