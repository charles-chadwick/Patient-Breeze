import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useContactManager(reloadKey) {
    const contact_modal_open = ref(false)
    const editing_contact = ref(null)
    const confirm_open = ref(false)
    const deleting_contact = ref(null)
    const deleting = ref(false)

    function openCreateContact() {
        editing_contact.value = null
        contact_modal_open.value = true
    }

    function openEditContact(contact) {
        editing_contact.value = contact
        contact_modal_open.value = true
    }

    function handleContactSaved() {
        router.reload({ only: [reloadKey] })
    }

    function askDeleteContact(contact) {
        deleting_contact.value = contact
        confirm_open.value = true
    }

    function confirmDeleteContact() {
        if (!deleting_contact.value) { return }
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

    return {
        contact_modal_open,
        editing_contact,
        confirm_open,
        deleting_contact,
        deleting,
        openCreateContact,
        openEditContact,
        handleContactSaved,
        askDeleteContact,
        confirmDeleteContact,
    }
}
