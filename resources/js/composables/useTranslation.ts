import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'

/**
 * Shell translations bound to the current locale.
 * `translations` is a shared prop keyed by locale ({ en: {...}, fr: {...} }),
 * so switching locale never needs a round trip for shell strings.
 */
export function useTranslation() {
    const page = usePage<PageProps>()

    const locale = computed(() => page.props.locale)

    const t = (key: string, params: Record<string, string | number> = {}): string => {
        const dict = page.props.translations?.[locale.value]
        let text = dict?.[key] ?? key
        for (const [name, value] of Object.entries(params)) {
            text = text.replaceAll(`:${name}`, String(value))
        }
        return text
    }

    /**
     * Prefixes a path with the active locale, the one URL shape every page
     * shares. `targetLocale` lets the language switcher link to the same path
     * under another locale without duplicating the prefixing rule.
     */
    const localeUrl = (path: string, targetLocale: string = locale.value): string => `/${targetLocale}${path}`

    return { t, localeUrl }
}
