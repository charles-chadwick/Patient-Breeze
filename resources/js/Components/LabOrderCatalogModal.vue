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
import LabOrderForm from '@/Pages/LabOrders/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    labOrder: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const isEditing = computed(() => props.labOrder !== null)

const formAction = computed(() =>
    isEditing.value
        ? route('lab-orders.update', props.labOrder.id)
        : route('lab-orders.store')
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
                        ? $t('lab_orders.catalog.form.edit_title', { name: labOrder.name })
                        : $t('lab_orders.catalog.form.new_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('lab_orders.catalog.form.hint') }}</DialogDescription>
            </DialogHeader>

            <LabOrderForm
                v-if="open"
                :key="labOrder?.id ?? 'new'"
                :action="formAction"
                :method="formMethod"
                :lab-order="labOrder"
                form-id="lab-order-catalog-form"
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
                    form="lab-order-catalog-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ $t('lab_orders.catalog.form.submit') }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
