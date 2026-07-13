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
import MedicationForm from '@/Pages/Medications/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    medication: {
        type: Object,
        default: null,
    },
    dose_form_options: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.medication !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('medications.update', props.medication.id)
        : route('medications.store')
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
                        ? $t('medications.catalog.form.edit_title', { name: medication.name })
                        : $t('medications.catalog.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('medications.catalog.form.hint') }}</DialogDescription>
            </DialogHeader>

            <MedicationForm
                v-if="open"
                :key="medication?.id ?? 'new'"
                :action="formAction"
                :method="formMethod"
                :medication="medication"
                :dose_form_options="dose_form_options"
                form-id="medication-catalog-form"
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
                    form="medication-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('medications.catalog.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
