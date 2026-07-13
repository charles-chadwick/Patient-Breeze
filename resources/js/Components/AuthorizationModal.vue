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
import { useAuthorizationModal } from '@/composables/useAuthorizationModal'

const { state, dismiss } = useAuthorizationModal()

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
                <DialogTitle>{{ $t('errors.unauthorized.title') }}</DialogTitle>
                <DialogDescription>{{ state.message }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="dismiss">{{ $t('errors.ok') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
