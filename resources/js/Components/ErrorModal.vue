<script setup>
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { useErrorModal } from '@/composables/useErrorModal'

const { state, dismiss } = useErrorModal()

function handleOpenUpdate(value) {
    if (!value) {
        dismiss()
    }
}
</script>

<template>
    <Dialog :open="state.is_open" @update:open="handleOpenUpdate">
        <DialogContent>
            <DialogHeader>
                <p class="text-sm font-bold uppercase tracking-wide text-accent">{{ state.status }}</p>
                <DialogTitle>{{ state.title }}</DialogTitle>
                <DialogDescription>{{ state.description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="dismiss">{{ $t('errors.ok') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
