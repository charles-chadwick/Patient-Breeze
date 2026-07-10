<script setup>
import { computed } from 'vue'
import { trans } from 'laravel-vue-i18n'
import { formatDate, DATE_SHORT } from '@/lib/utils'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import EncounterNoteForm from '@/Pages/EncounterNotes/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    patientId: {
        type: Number,
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
    ownerOptions: {
        type: Array,
        default: () => [],
    },
    appointments: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:open', 'saved'])

const is_edit = computed(() => Boolean(props.note?.id))

const is_readonly = computed(() => Boolean(props.note) && !props.note.can_edit)

const action = computed(() =>
    is_edit.value
        ? route('patients.encounter-notes.update', [props.patientId, props.note.id])
        : route('patients.encounter-notes.store', props.patientId),
)

const method = computed(() => (is_edit.value ? 'put' : 'post'))

const title = computed(() => {
    if (is_readonly.value) {
        return trans('encounter_notes.modal.view_title')
    }

    return is_edit.value
        ? trans('encounter_notes.modal.edit_title')
        : trans('encounter_notes.modal.new_title')
})

const description = computed(() => {
    if (is_readonly.value) {
        return trans('encounter_notes.modal.view_description')
    }

    return is_edit.value
        ? trans('encounter_notes.modal.edit_description')
        : trans('encounter_notes.modal.new_description')
})

const submit_label = computed(() =>
    is_edit.value
        ? trans('encounter_notes.modal.submit_update')
        : trans('encounter_notes.modal.submit_create'),
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
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div v-if="is_readonly" data-testid="encounter-note-view-content" class="grid gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('encounter_notes.form.label_type') }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">{{ note.type_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('encounter_notes.form.label_encounter_date') }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">{{ formatDate(note.encounter_date, DATE_SHORT) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('encounter_notes.form.label_owner') }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">{{ note.author_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('encounter_notes.columns.status') }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">{{ note.status_label }}</p>
                    </div>
                    <div v-if="note.signer_name">
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ $t('encounter_notes.columns.signatures') }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">
                            {{ $t('encounter_notes.signed_by', { name: note.signer_name }) }}
                        </p>
                        <p v-if="note.co_signer_name" class="text-sm text-foreground">
                            {{ $t('encounter_notes.co_signed_by', { name: note.co_signer_name }) }}
                        </p>
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('encounter_notes.form.label_title') }}
                    </p>
                    <p class="text-sm font-bold text-foreground">{{ note.title }}</p>
                </div>

                <div>
                    <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                        {{ $t('encounter_notes.form.label_content') }}
                    </p>
                    <div class="encounter-note-content rounded-lg border border-border bg-background p-4 text-sm text-foreground" v-html="note.content"></div>
                </div>
            </div>

            <EncounterNoteForm
                v-else
                :key="note?.id ?? 'new'"
                :action="action"
                :method="method"
                :note="note"
                :types="types"
                :owner-options="ownerOptions"
                :appointments="appointments"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ is_readonly ? $t('common.actions.close') : $t('common.actions.cancel') }}
                </button>
                <button
                    v-if="!is_readonly"
                    type="submit"
                    form="encounter-note-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ submit_label }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.encounter-note-content :deep(p) {
    margin: 0 0 0.75rem;
}

.encounter-note-content :deep(p:last-child) {
    margin-bottom: 0;
}

.encounter-note-content :deep(ul),
.encounter-note-content :deep(ol) {
    margin: 0 0 0.75rem;
    padding-left: 1.5rem;
}

.encounter-note-content :deep(ul) {
    list-style: disc;
}

.encounter-note-content :deep(ol) {
    list-style: decimal;
}

.encounter-note-content :deep(a) {
    color: var(--primary);
    text-decoration: underline;
}
</style>
