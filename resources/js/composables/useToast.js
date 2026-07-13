import { reactive } from 'vue'

/**
 * Toast notification types. Mirrors the flash keys shared from the server
 * (`flash.success` / `flash.error`) plus client-only info toasts.
 */
export const ToastType = Object.freeze({
    Success: 'Success',
    Error: 'Error',
    Info: 'Info',
})

const DEFAULT_DURATION = 4000

// Shared, module-level state so every caller manipulates the same stack. The
// <Toaster> component renders it; any component can push to it.
const state = reactive({
    toasts: [],
})

let next_id = 0

export function useToast() {
    function dismissToast(id) {
        const index = state.toasts.findIndex((toast) => toast.id === id)

        if (index !== -1) {
            state.toasts.splice(index, 1)
        }
    }

    /**
     * Push a toast onto the stack. Returns its id so callers can dismiss it
     * early. A non-positive duration keeps the toast until dismissed manually.
     */
    function pushToast(message, { type = ToastType.Success, duration = DEFAULT_DURATION } = {}) {
        const id = ++next_id

        state.toasts.push({ id, message, type })

        if (duration > 0) {
            setTimeout(() => dismissToast(id), duration)
        }

        return id
    }

    function toastSuccess(message, options = {}) {
        return pushToast(message, { ...options, type: ToastType.Success })
    }

    function toastError(message, options = {}) {
        return pushToast(message, { ...options, type: ToastType.Error })
    }

    return { state, pushToast, toastSuccess, toastError, dismissToast }
}
