import { reactive } from 'vue'
import { trans } from 'laravel-vue-i18n'

const state = reactive({
    is_open: false,
    status: null,
    title: '',
    description: '',
})

/**
 * Resolve a status-specific error string, falling back to the generic copy when
 * a status has no dedicated entry. laravel-vue-i18n echoes the lookup key back
 * for missing strings, which is how we detect the gap.
 */
function resolveErrorCopy(status, key) {
    const lookup_key = `errors.status.${status}.${key}`
    const value = trans(lookup_key)

    return value === lookup_key ? trans(`errors.status.generic.${key}`) : value
}

export function useErrorModal() {
    function show(status) {
        state.status = status
        state.title = resolveErrorCopy(status, 'title')
        state.description = resolveErrorCopy(status, 'description')
        state.is_open = true
    }

    function dismiss() {
        state.is_open = false
    }

    return { state, show, dismiss }
}
