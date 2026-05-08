<script setup>
import { ref } from 'vue'
import { router, setLayoutProps } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ContactModal from '@/Components/ContactModal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import RoiBadge from '@/Components/RoiBadge.vue'

defineOptions({ layout: DashboardLayout })

setLayoutProps({ title: 'Contacts' })

const props = defineProps({
    contacts: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
})

const modal_open = ref(false)
const editing_contact = ref(null)

const confirm_open = ref(false)
const deleting_contact = ref(null)
const deleting = ref(false)

function openEdit(contact) {
    editing_contact.value = contact
    modal_open.value = true
}

function handleSaved() {
    router.reload({ only: ['contacts'] })
}

function askDelete(contact) {
    deleting_contact.value = contact
    confirm_open.value = true
}

function confirmDelete() {
    if (!deleting_contact.value) return
    deleting.value = true
    router.delete(route('contacts.destroy', deleting_contact.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false
            confirm_open.value = false
            deleting_contact.value = null
        },
    })
}

function contactableLabel(contact) {
    if (!contact.contactable_type) return '—'
    const short = contact.contactable_type.split('\\').pop()
    return `${short} #${contact.contactable_id}`
}
</script>

<template>
    <div class="rounded border border-border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-foreground">All Contacts</h2>
                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                    {{ contacts.total }}
                </span>
            </div>
        </div>

        <div v-if="contacts.data.length === 0" class="px-6 py-8 text-center text-sm text-muted-foreground">
            No contacts on record.
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-6 py-3 font-bold text-muted-foreground">Name</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Type</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Phone</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">Linked To</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground">ROI</th>
                    <th class="px-6 py-3 font-bold text-muted-foreground text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="contact in contacts.data"
                    :key="contact.id"
                    class="border-b border-border last:border-b-0"
                >
                    <td class="px-6 py-3 font-bold text-foreground">{{ contact.name }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">
                            {{ contact.type }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted-foreground">{{ contact.phone || '—' }}</td>
                    <td class="px-6 py-3 text-muted-foreground">{{ contactableLabel(contact) }}</td>
                    <td class="px-6 py-3">
                        <RoiBadge :value="contact.roi" />
                    </td>
                    <td class="px-6 py-3 text-right">
                        <button
                            type="button"
                            @click="openEdit(contact)"
                            class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-foreground hover:bg-muted/40"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            @click="askDelete(contact)"
                            class="ml-2 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <ContactModal
            v-model:open="modal_open"
            :contact="editing_contact"
            :types="types"
            @saved="handleSaved"
        />

        <ConfirmDialog
            v-model:open="confirm_open"
            title="Delete contact?"
            :description="deleting_contact ? `This will permanently remove ${deleting_contact.name}.` : ''"
            confirm-label="Delete"
            :processing="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
