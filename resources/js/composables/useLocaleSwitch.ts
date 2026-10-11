import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'

/** Shared so the header toggle and the account menu point at the same place. */
export function useLocaleSwitch() {
    const page = usePage<PageProps>()

    const currentLocale = computed(() => {
        const match = page.url.match(/^\/(en|fr)(\b|\/|\?|$)/)

        return match ? match[1] : page.props.locale
    })

    const targetLocale = computed(() => (currentLocale.value === 'en' ? 'fr' : 'en'))

    const targetHref = computed(() => {
        const prefix = `/${currentLocale.value}`

        if (page.url === prefix) {
            return `/${targetLocale.value}`
        }

        if (page.url.startsWith(`${prefix}/`) || page.url.startsWith(`${prefix}?`)) {
            return `/${targetLocale.value}${page.url.slice(prefix.length)}`
        }

        return `/${targetLocale.value}`
    })

    // Hardcoded action strings, as in LanguageToggle.vue today: they name the
    // target language, so they are not part of the translated shell dictionary.
    const label = computed(() => (currentLocale.value === 'en' ? 'Passer en Français' : 'Switch to English'))
    const displayCode = computed(() => targetLocale.value.toUpperCase())

    return { targetHref, label, displayCode }
}
