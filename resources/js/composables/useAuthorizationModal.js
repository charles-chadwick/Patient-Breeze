import { reactive } from 'vue'

const state = reactive({
    is_open: false,
    message: '',
})

const DEFAULT_MESSAGE = 'You are not authorized to perform this action.'

export function useAuthorizationModal() {
    function showDenied(message = DEFAULT_MESSAGE) {
        state.message = message
        state.is_open = true
    }

    function dismiss() {
        state.is_open = false
    }

    return { state, showDenied, dismiss }
}
