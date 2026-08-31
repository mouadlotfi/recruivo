<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

const page = usePage<PageProps>()

const currentLocale = computed(() => {
    const url = page.url || ''
    const match = url.match(/^\/(en|fr)(\b|\/|\?|$)/)
    if (match) {
        return match[1]
    }
    return page.props.locale ?? 'en'
})

const targetLocale = computed(() => (currentLocale.value === 'en' ? 'fr' : 'en'))

const targetHref = computed(() => {
    const url = page.url || `/${currentLocale.value}`
    const prefix = `/${currentLocale.value}`

    if (url === prefix) {
        return `/${targetLocale.value}`
    }

    if (url.startsWith(`${prefix}/`) || url.startsWith(`${prefix}?`)) {
        return `/${targetLocale.value}${url.slice(prefix.length)}`
    }

    return `/${targetLocale.value}`
})

const label = computed(() => (currentLocale.value === 'en' ? 'Passer en Français' : 'Switch to English'))
const displayCode = computed(() => targetLocale.value.toUpperCase())
</script>

<template>
    <Link
        :href="targetHref"
        :title="label"
        :aria-label="label"
        class="inline-flex h-9 items-center gap-1.5 rounded-full px-2.5 text-xs font-semibold text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:px-3 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100"
    >
        <svg class="h-4 w-4 text-stone-500 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
        </svg>
        <span class="font-medium tracking-wide">{{ displayCode }}</span>
    </Link>
</template>
