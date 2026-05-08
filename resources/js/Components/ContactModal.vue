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
import ContactForm from '@/Pages/Contacts/Partials/Form.vue'

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    contact: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    contactableType: {
        type: String,
        default: null,
    },
    contactableId: {
        type: [Number, String],
        default: null,
    },
})

const emit = defineEmits(['update:open', 'saved'])

const is_edit = computed(() => Boolean(props.contact?.id))

const action = computed(() =>
    is_edit.value
        ? route('contacts.update', props.contact.id)
        : route('contacts.store'),
)

const method = computed(() => (is_edit.value ? 'patch' : 'post'))

const title = computed(() => (is_edit.value ? 'Edit Contact' : 'New Contact'))

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
                    {{ is_edit ? 'Update this contact’s details.' : 'Add a new contact.' }}
                </DialogDescription>
            </DialogHeader>

            <ContactForm
                :key="contact?.id ?? 'new'"
                :action="action"
                :method="method"
                :contact="contact"
                :types="types"
                :contactable-type="contactableType"
                :contactable-id="contactableId"
                @success="handleSuccess"
            />

            <DialogFooter>
                <button
                    type="button"
                    @click="handleOpenUpdate(false)"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-bold text-foreground hover:bg-muted/40"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    form="contact-form"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary/90"
                >
                    {{ is_edit ? 'Save Changes' : 'Create Contact' }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
