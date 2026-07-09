<script setup>
import NoteModal from '@/Components/NoteModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useNoteManager } from '@/composables/useNoteManager'
import { formatDate, DATE_SHORT } from '@/lib/utils'

const props = defineProps({
    notes: {
        type: Array,
        default: null,
    },
    notableType: {
        type: String,
        required: true,
    },
    notableId: {
        type: Number,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const {
    note_modal_open,
    editing_note,
    confirm_open,
    deleting_note,
    deleting,
    openCreateNote,
    openEditNote,
    handleNoteSaved,
    askDeleteNote,
    confirmDeleteNote,
} = useNoteManager()

function snippet(html) {
    const text = (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    return text.length > 80 ? text.slice(0, 80) + '…' : text
}
</script>

<template>
    <div class="flex items-center justify-between px-6 py-4">
        <h2 class="font-bold text-foreground">{{ $t('notes.tab.heading') }}</h2>
        <button
            type="button"
            @click="openCreateNote"
            class="inline-flex h-10 items-center rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary/90"
        >
            {{ $t('notes.tab.new_note') }}
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
        {{ $t('notes.tab.empty') }}
    </div>

    <table v-else class="w-full text-sm">
        <thead>
            <tr class="border-b border-border text-left">
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_title') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_type') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground">{{ $t('notes.tab.column_updated') }}</th>
                <th class="px-6 py-3 font-bold text-muted-foreground text-right">{{ $t('notes.tab.column_actions') }}</th>
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
                </td>
                <td class="px-6 py-3">
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                        {{ $t('enums.note_type.' + note.type) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-muted-foreground">{{ formatDate(note.updated_at, DATE_SHORT) }}</td>
                <td class="px-6 py-3 text-right">
                    <button
                        type="button"
                        @click="openEditNote(note)"
                        class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                    >
                        {{ $t('common.actions.edit') }}
                    </button>
                    <button
                        type="button"
                        @click="askDeleteNote(note)"
                        class="ml-2 rounded-lg border border-vibrant-coral-200 px-3 py-1.5 text-xs font-bold text-vibrant-coral-600 hover:bg-vibrant-coral-50"
                    >
                        {{ $t('common.actions.delete') }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <NoteModal
        v-model:open="note_modal_open"
        :note="editing_note"
        :types="types"
        :notable-type="notableType"
        :notable-id="notableId"
        @saved="handleNoteSaved"
    />

    <ConfirmDialog
        v-model:open="confirm_open"
        :title="$t('notes.confirm.delete_title')"
        :description="deleting_note ? $t('notes.confirm.delete_description', { title: deleting_note.title }) : ''"
        :confirm-label="$t('common.actions.delete')"
        :processing="deleting"
        @confirm="confirmDeleteNote"
    />
</template>
