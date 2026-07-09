// Class-based theme application. The CSS defines the `dark` variant as
// `&:is(.dark *)`, so switching themes is a matter of toggling the `.dark`
// class on <html>. Supported themes match the SettingKey::Theme enum values:
// 'System', 'Light', and 'Dark'.

const dark_media_query = '(prefers-color-scheme: dark)'

let system_listener = null

function applyDarkClass(is_dark) {
    document.documentElement.classList.toggle('dark', is_dark)
}

function stopWatchingSystem() {
    if (system_listener) {
        window.matchMedia(dark_media_query).removeEventListener('change', system_listener)
        system_listener = null
    }
}

/**
 * Apply a theme, toggling the `.dark` class on <html>. When the theme is
 * 'System', it tracks the OS preference and updates live as it changes.
 */
export function applyTheme(theme) {
    stopWatchingSystem()

    if (theme === 'System') {
        const media = window.matchMedia(dark_media_query)
        system_listener = (event) => applyDarkClass(event.matches)
        media.addEventListener('change', system_listener)
        applyDarkClass(media.matches)

        return
    }

    applyDarkClass(theme === 'Dark')
}
