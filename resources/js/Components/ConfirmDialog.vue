<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: 'Are you sure?',
    },
    description: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Confirm',
    },
    cancelLabel: {
        type: String,
        default: 'Cancel',
    },
    destructive: {
        type: Boolean,
        default: true,
    },
    processing: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:open', 'confirm'])

function handleCancel() {
    emit('update:open', false)
}

function handleConfirm() {
    emit('confirm')
}

function handleOpenUpdate(value) {
    emit('update:open', value)
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenUpdate">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <button
                    type="button"
                    @click="handleCancel"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    :disabled="processing"
                    @click="handleConfirm"
                    class="rounded-lg px-4 py-2 text-sm font-bold text-white disabled:opacity-50"
                    :class="destructive ? 'bg-red-600 hover:bg-red-700' : 'bg-primary hover:bg-primary/90'"
                >
                    {{ confirmLabel }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
