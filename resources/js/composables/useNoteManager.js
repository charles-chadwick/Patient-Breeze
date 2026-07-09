import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export function useNoteManager() {
    const note_modal_open = ref(false)
    const editing_note = ref(null)
    const confirm_open = ref(false)
    const deleting_note = ref(null)
    const deleting = ref(false)

    function openCreateNote() {
        editing_note.value = null
        note_modal_open.value = true
    }

    function openEditNote(note) {
        editing_note.value = note
        note_modal_open.value = true
    }

    function handleNoteSaved() {
        router.reload({ only: ['notes'] })
    }

    function askDeleteNote(note) {
        deleting_note.value = note
        confirm_open.value = true
    }

    function confirmDeleteNote() {
        if (!deleting_note.value) { return }
        deleting.value = true
        router.delete(route('notes.destroy', deleting_note.value.id), {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false
                confirm_open.value = false
                deleting_note.value = null
            },
        })
    }

    return {
        note_modal_open,
        editing_note,
        confirm_open,
        deleting_note,
        deleting,
        openCreateNote,
        openEditNote,
        handleNoteSaved,
        askDeleteNote,
        confirmDeleteNote,
    }
}
