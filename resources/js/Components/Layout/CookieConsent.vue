<script setup lang="ts">
import { onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { consentOpen, readConsent, storeConsent } from '../../composables/useCookieConsent'

const page = usePage<PageProps>()
const { t } = useTranslation()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

onMounted(() => {
    if (readConsent() === null) consentOpen.value = true
})

// Adds the tags the shell withholds until consent, so accepting needs no reload.
// The URL comes from the shell (meta[name="font-stylesheet"]), not from here.
const enableFonts = () => {
    const url = document.querySelector<HTMLMetaElement>('meta[name="font-stylesheet"]')?.content
    if (!url || document.querySelector('link[data-consented-fonts]')) return

    for (const [href, crossorigin] of [['https://fonts.googleapis.com', false], ['https://fonts.gstatic.com', true]] as const) {
        const preconnect = document.createElement('link')
        preconnect.rel = 'preconnect'
        preconnect.href = href
        preconnect.dataset.consentedFonts = 'true'
        if (crossorigin) preconnect.crossOrigin = 'anonymous'
        document.head.appendChild(preconnect)
    }

    const stylesheet = document.createElement('link')
    stylesheet.rel = 'stylesheet'
    stylesheet.href = url
    stylesheet.dataset.consentedFonts = 'true'
    document.head.appendChild(stylesheet)
}

const accept = () => {
    enableFonts()
    storeConsent('accepted')
}

const necessaryOnly = () => storeConsent('necessary')
</script>

<template>
    <div
        v-if="consentOpen"
        role="dialog"
        aria-live="polite"
        :aria-label="t('cookie_settings')"
        class="fixed inset-x-0 bottom-0 z-[9998] border-t border-stone-200 bg-white/95 px-4 py-5 shadow-2xl backdrop-blur dark:border-stone-800 dark:bg-stone-950/95"
    >
        <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                {{ t('cookie_notice') }}
                <Link :href="localeUrl('/privacy')" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
                    {{ t('privacy_policy') }}
                </Link>
            </p>
            <div class="flex shrink-0 flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:bg-stone-800"
                    @click="necessaryOnly"
                >
                    {{ t('cookie_necessary') }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-950"
                    @click="accept"
                >
                    {{ t('cookie_accept') }}
                </button>
            </div>
        </div>
    </div>
</template>
