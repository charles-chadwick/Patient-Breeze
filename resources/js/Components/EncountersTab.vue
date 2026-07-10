<script setup>
import EncounterNoteModal from '@/Components/EncounterNoteModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useEncounterNoteManager } from '@/composables/useEncounterNoteManager'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
    patientId: {
        type: Number,
        required: true,
    },
    notes: {
        type: Array,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    appointments: {
        type: Array,
        default: () => [],
    },
})

const {
    modal_open,
    editing_note,
    confirm_open,
    deleting_note,
    deleting,
    openCreate,
    openNote,
    handleSaved,
    askDelete,
    confirmDelete,
    sign,
    coSign,
    unsign,
} = useEncounterNoteManager(props.patientId)

const statusClasses = {
    Unsigned: 'bg-light-yellow-100 text-light-yellow-700',
    Signed: 'bg-tropical-teal-100 text-tropical-teal-700',
    CoSigned: 'bg-cerulean-100 text-cerulean-700',
}

function snippet(html) {
    const text = (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    return text.length > 80 ? text.slice(0, 80) + '…' : text
}
</script>

<template>
    <div class="flex items-center justify-between px-6 py-4">
        <h2 class="font-bold text-foreground">{{ $t('encounter_notes.tab.heading') }}</h2>
        <button
            type="button"
            data-testid="new-encounter-note-button"
            @click="openCreate"
            class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
        >
            {{ $t('encounter_notes.tab.new') }}
        </button>
    </div>

    <div v-if="notes === null" class="divide-y divide-border">
        <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-6 py-4">
            <div class="h-4 w-48 animate-pulse rounded bg-muted"></div>
            <div class="ml-auto h-4 w-24 animate-pulse rounded bg-muted"></div>
        </div>
    </div>

    <div
        v-else-if="notes.length === 0"
        class="px-6 py-8 text-center text-sm text-muted-foreground"
    >
        {{ $t('encounter_notes.tab.empty') }}
    </div>

    <table v-else class="w-full text-sm">
        <thead>
            <tr class="border-b border-border text-left">
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.title') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.type') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.encounter_date') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('encounter_notes.columns.status') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('encounter_notes.columns.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            <tr
                v-for="note in notes"
                :key="note.id"
                class="hover:bg-muted/40"
            >
                <td class="px-6 py-3">
                    <div class="font-bold text-foreground">{{ note.title }}</div>
                    <div class="text-xs text-muted-foreground">{{ snippet(note.content) }}</div>
                    <div v-if="note.signer_name" class="mt-0.5 text-xs text-muted-foreground">
                        {{ $t('encounter_notes.signed_by', { name: note.signer_name }) }}
                        <template v-if="note.co_signer_name">
                            · {{ $t('encounter_notes.co_signed_by', { name: note.co_signer_name }) }}
                        </template>
                    </div>
                </td>
                <td class="px-6 py-3">
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ note.type_label }}
                    </span>
                </td>
                <td class="px-6 py-3 text-muted-foreground">{{ formatDate(note.encounter_date, DATE_SHORT) }}</td>
                <td class="px-6 py-3">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="statusClasses[note.status] ?? 'bg-muted text-muted-foreground'"
                    >
                        {{ note.status_label }}
                    </span>
                </td>
                <td class="px-6 py-3 text-right whitespace-nowrap">
                    <button
                        v-if="note.can_sign"
                        type="button"
                        data-testid="encounter-note-sign"
                        @click="sign(note)"
                        class="rounded-lg border border-tropical-teal-200 px-3 py-1.5 text-xs font-bold text-tropical-teal-700 hover:bg-tropical-teal-50"
                    >
                        {{ $t('encounter_notes.actions.sign') }}
                    </button>
                    <button
                        v-if="note.can_co_sign"
                        type="button"
                        @click="coSign(note)"
                        class="ml-2 rounded-lg border border-cerulean-200 px-3 py-1.5 text-xs font-bold text-cerulean-700 hover:bg-cerulean-50"
                    >
                        {{ $t('encounter_notes.actions.co_sign') }}
                    </button>
                    <button
                        v-if="note.can_unsign"
                        type="button"
                        data-testid="encounter-note-unsign"
                        @click="unsign(note)"
                        class="ml-2 rounded-lg border border-light-yellow-200 px-3 py-1.5 text-xs font-bold text-light-yellow-700 hover:bg-light-yellow-50"
                    >
                        {{ $t('encounter_notes.actions.unsign') }}
                    </button>
                    <button
                        v-if="note.can_edit"
                        type="button"
                        data-testid="encounter-note-edit"
                        @click="openNote(note)"
                        class="ml-2 rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('encounter_notes.actions.edit') }}
                    </button>
                    <button
                        v-else
                        type="button"
                        data-testid="encounter-note-view"
                        @click="openNote(note)"
                        class="ml-2 rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('encounter_notes.actions.view') }}
                    </button>
                    <button
                        v-if="note.can_delete"
                        type="button"
                        @click="askDelete(note)"
                        class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('encounter_notes.actions.delete') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <EncounterNoteModal
        v-model:open="modal_open"
        :patient-id="patientId"
        :note="editing_note"
        :types="types"
        :appointments="appointments"
        @saved="handleSaved"
    />

    <ConfirmDialog
        v-model:open="confirm_open"
        :title="trans('encounter_notes.actions.delete')"
        :description="deleting_note ? trans('encounter_notes.delete_confirm') : ''"
        :confirm-label="trans('encounter_notes.actions.delete')"
        :processing="deleting"
        @confirm="confirmDelete"
    />
</template>
