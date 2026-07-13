import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useEncounterNoteManager(patientId) {
    const modal_open = ref(false)
    const editing_note = ref(null)
    const confirm_open = ref(false)
    const deleting_note = ref(null)
    const deleting = ref(false)
    const unsign_open = ref(false)
    const unsigning_note = ref(null)
    const unsigning = ref(false)

    function openCreate() {
        editing_note.value = null
        modal_open.value = true
    }

    function openNote(note) {
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

    function askUnsign(note) {
        unsigning_note.value = note
        unsign_open.value = true
    }

    function confirmUnsign() {
        if (!unsigning_note.value) {
            return
        }

        unsigning.value = true

        router.post(route('patients.encounter-notes.unsign', [patientId, unsigning_note.value.id]), {}, {
            preserveScroll: true,
            only: ['encounter_notes'],
            onFinish: () => {
                unsigning.value = false
                unsign_open.value = false
                unsigning_note.value = null
            },
        })
    }

    return {
        modal_open,
        editing_note,
        confirm_open,
        deleting_note,
        deleting,
        unsign_open,
        unsigning_note,
        unsigning,
        openCreate,
        openNote,
        handleSaved,
        askDelete,
        confirmDelete,
        sign,
        coSign,
        askUnsign,
        confirmUnsign,
    }
}
