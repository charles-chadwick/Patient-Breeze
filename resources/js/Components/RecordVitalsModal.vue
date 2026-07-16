<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import VitalsForm from '@/Components/VitalsForm.vue'

defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    action: {
        type: String,
        required: true,
    },
    positionOptions: {
        type: Array,
        default: () => [],
    },
    temperatureSiteOptions: {
        type: Array,
        default: () => [],
    },
    oxygenDeliveryOptions: {
        type: Array,
        default: () => [],
    },
    staffOptions: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:open', 'saved'])

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
                <DialogTitle>{{ $t('vitals.form.heading') }}</DialogTitle>
                <DialogDescription>{{ $t('vitals.form.hint') }}</DialogDescription>
            </DialogHeader>

            <VitalsForm
                :action="action"
                :position-options="positionOptions"
                :temperature-site-options="temperatureSiteOptions"
                :oxygen-delivery-options="oxygenDeliveryOptions"
                :staff-options="staffOptions"
                form-id="patient-vitals-form"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ $t('vitals.form.cancel') }}
                </button>
                <button
                    type="submit"
                    form="patient-vitals-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('vitals.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
