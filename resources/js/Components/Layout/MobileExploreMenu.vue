<script setup lang="ts">
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { useDismiss } from '../../composables/useDismiss'

// The trigger styling lives with the nav item list that owns it (the recruiter
// and candidate branches share the item classes), so the resolved class list
// is passed in. Callers set their own `data-*` marker via attribute fallthrough.
defineProps<{ buttonClass: string[] }>()

const page = usePage<PageProps>()
const { t } = useTranslation()

const localeUrl = (path: string) => `/${page.props.locale}${path}`

const open = ref(false)
const root = ref<HTMLElement | null>(null)
useDismiss(open, root)

const itemClass = 'block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300'
const panelClass = 'absolute bottom-full right-0 mb-2 w-max min-w-36 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 text-left shadow-xl dark:border-stone-700 dark:bg-stone-900'
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            aria-haspopup="menu"
            :aria-expanded="open"
            :class="buttonClass"
            @click="open = !open"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
            <span class="mt-1 text-[11px] font-medium">{{ t('explore') }}</span>
        </button>
        <div v-if="open" role="menu" :class="panelClass">
            <Link :href="localeUrl('/jobs')" role="menuitem" :class="itemClass">{{ t('jobs') }}</Link>
            <Link :href="localeUrl('/companies')" role="menuitem" :class="itemClass">{{ t('companies') }}</Link>
        </div>
    </div>
</template>
