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
                <DialogTitle>Not Authorized</DialogTitle>
                <DialogDescription>{{ state.message }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="dismiss">OK</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
