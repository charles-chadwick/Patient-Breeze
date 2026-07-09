<script setup>
import { computed } from 'vue'
import { trans } from 'laravel-vue-i18n'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import NoteForm from '@/Pages/Notes/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    note: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    notableType: {
        type: String,
        default: null,
    },
    notableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const is_edit = computed(() => Boolean(props.note?.id))

const action = computed(() =>
    is_edit.value
        ? route('notes.update', props.note.id)
        : route('notes.store'),
)

const method = computed(() => (is_edit.value ? 'patch' : 'post'))

const title = computed(() =>
    is_edit.value
        ? trans('notes.modal.edit_title')
        : trans('notes.modal.new_title'),
)

const description = computed(() =>
    is_edit.value
        ? trans('notes.modal.edit_description')
        : trans('notes.modal.new_description'),
)

const submit_label = computed(() =>
    is_edit.value
        ? trans('notes.modal.submit_update')
        : trans('notes.modal.submit_create'),
)

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <NoteForm
                :key="note?.id ?? 'new'"
                :action="action"
                :method="method"
                :note="note"
                :types="types"
                :notable-type="notableType"
                :notable-id="notableId"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('common.actions.cancel') }}
                </button>
                <button
                    type="submit"
                    form="note-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ submit_label }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
