<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'

const page = usePage<PageProps>()
const { t } = useTranslation()
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const year = new Date().getFullYear()

const linkClasses = 'transition hover:text-amber-600 dark:hover:text-amber-400'
</script>

<template>
    <footer class="border-t border-stone-200 bg-white/70 py-6 text-sm text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-950/80 dark:text-stone-400">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-center gap-3 px-4 text-center sm:flex-row sm:justify-between sm:gap-2 sm:px-6 sm:text-left">
            <div class="space-y-1 text-center sm:text-left">
                <p class="max-w-md">{{ t('footer_text', { year }) }}</p>
                <p v-if="page.props.isDemoEnvironment && page.props.productionUrl">
                    {{ t('demo_production_notice') }}
                    <a
                        :href="page.props.productionUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 align-middle font-medium text-amber-600 transition hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                    >
                        {{ t('demo_production_link') }}
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                </p>
            </div>
            <nav :aria-label="t('legal_links')" class="flex flex-wrap items-center justify-center gap-4">
                <Link :href="localeUrl('/contact')" :class="linkClasses">{{ t('contact') }}</Link>
                <Link :href="localeUrl('/privacy')" :class="linkClasses">{{ t('privacy_policy') }}</Link>
                <Link :href="localeUrl('/terms')" :class="linkClasses">{{ t('terms_of_service') }}</Link>            </nav>
        </div>
    </footer>
</template>
