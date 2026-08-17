<script setup lang="ts">
import { useTranslation } from '../../composables/useTranslation'

const { t } = useTranslation()

// Port of resources/js/theme.js: toggles .dark on <html> + localStorage recruivo:theme.
// inertia.blade.php sets the initial class from the recruivo:theme cookie (dark when absent).
const THEME_COOKIE = 'recruivo:theme'

const syncTheme = (dark: boolean) => {
    const value = dark ? 'dark' : 'light'
    document.documentElement.classList.toggle('dark', dark)
    localStorage.setItem(THEME_COOKIE, value)
    document.cookie = `${THEME_COOKIE}=${value}; path=/; max-age=31536000`
}

// Re-apply the stored preference on mount so the class matches localStorage
// even when the cookie is absent (e.g. toggles from before cookies were written).
const stored = localStorage.getItem(THEME_COOKIE)
if (stored === 'light' || stored === 'dark') syncTheme(stored === 'dark')

const toggleTheme = () => syncTheme(!document.documentElement.classList.contains('dark'))
</script>

<template>
    <button
        id="theme-toggle"
        type="button"
        :aria-label="t('toggle_theme')"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100"
        @click="toggleTheme"
    >
        <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
        <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
        <span class="sr-only">{{ t('toggle_theme') }}</span>
    </button>
</template>
