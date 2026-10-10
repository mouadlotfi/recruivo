// Syncs theme state across <html> class, localStorage, and cookie.
const THEME_COOKIE = 'recruivo:theme'

const syncTheme = (dark: boolean) => {
    document.documentElement.classList.toggle('dark', dark)
    localStorage.setItem(THEME_COOKIE, dark ? 'dark' : 'light')
    document.cookie = `${THEME_COOKIE}=${dark ? 'dark' : 'light'}; path=/; max-age=31536000`
}

// Applied once at module scope: the header button and the account-menu row toggle the
// same document state, and the page has no SSR (the Blade shell ships no markup).
const stored = localStorage.getItem(THEME_COOKIE)
if (stored === 'light' || stored === 'dark') syncTheme(stored === 'dark')

export function useTheme() {
    return {
        toggleTheme: () => syncTheme(!document.documentElement.classList.contains('dark')),
    }
}
