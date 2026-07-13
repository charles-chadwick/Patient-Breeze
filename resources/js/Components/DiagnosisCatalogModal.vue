<script setup>
import { computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import DiagnosisForm from '@/Pages/Diagnoses/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    diagnosis: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.diagnosis !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('diagnoses.update', props.diagnosis.id)
        : route('diagnoses.store')
)

const formMethod = computed(() => (isEditing.value ? 'put' : 'post'))

function handleOpenUpdate(value) {
    emit('update:open', value)
}

function handleSuccess() {
    emit('saved')
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing
                        ? $t('diagnoses.catalog.form.edit_title', { name: diagnosis.diagnosis })
                        : $t('diagnoses.catalog.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('diagnoses.catalog.form.hint') }}</DialogDescription>
            </DialogHeader>

            <DiagnosisForm
                v-if="open"
                :key="diagnosis?.id ?? 'new'"
                :action="formAction"
                :method="formMethod"
                :diagnosis="diagnosis"
                form-id="diagnosis-catalog-form"
                :show-actions="false"
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
                    form="diagnosis-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('diagnoses.catalog.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
