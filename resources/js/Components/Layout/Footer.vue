<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'

const page = usePage<PageProps>()
const { t } = useTranslation()
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const year = new Date().getFullYear()
</script>

<template>
    <footer class="border-t border-stone-200 bg-white/70 py-6 text-sm text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-950/80 dark:text-stone-400">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-center gap-3 px-4 text-center sm:flex-row sm:justify-between sm:gap-2 sm:px-6 sm:text-left">
            <p class="max-w-md">{{ t('footer_text', { year }) }}</p>
            <nav :aria-label="t('legal_links')" class="flex items-center gap-4 whitespace-nowrap">
                <Link :href="localeUrl('/privacy')" class="transition hover:text-amber-600 dark:hover:text-amber-400">
                    {{ t('privacy_policy') }}
                </Link>
                <Link :href="localeUrl('/terms')" class="transition hover:text-amber-600 dark:hover:text-amber-400">
                    {{ t('terms_of_service') }}
                </Link>
            </nav>
        </div>
    </footer>
</template>
