import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useEncounterNoteManager(patientId) {
    const modal_open = ref(false)
    const editing_note = ref(null)
    const confirm_open = ref(false)
    const deleting_note = ref(null)
    const deleting = ref(false)

    function openCreate() {
        editing_note.value = null
        modal_open.value = true
    }

    function openEdit(note) {
        editing_note.value = note
        modal_open.value = true
    }

    function handleSaved() {
        router.reload({ only: ['encounter_notes'] })
    }

    function askDelete(note) {
        deleting_note.value = note
        confirm_open.value = true
    }

    function confirmDelete() {
        if (!deleting_note.value) {
            return
        }

        deleting.value = true

        router.delete(route('patients.encounter-notes.destroy', [patientId, deleting_note.value.id]), {
            preserveScroll: true,
            only: ['encounter_notes'],
            onFinish: () => {
                deleting.value = false
                confirm_open.value = false
                deleting_note.value = null
            },
        })
    }

    function sign(note) {
        router.post(route('patients.encounter-notes.sign', [patientId, note.id]), {}, {
            preserveScroll: true,
            only: ['encounter_notes'],
        })
    }

    function coSign(note) {
        router.post(route('patients.encounter-notes.co-sign', [patientId, note.id]), {}, {
            preserveScroll: true,
            only: ['encounter_notes'],
        })
    }

    return {
        modal_open,
        editing_note,
        confirm_open,
        deleting_note,
        deleting,
        openCreate,
        openEdit,
        handleSaved,
        askDelete,
        confirmDelete,
        sign,
        coSign,
    }
}
