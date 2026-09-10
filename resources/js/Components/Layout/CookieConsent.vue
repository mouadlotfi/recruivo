<script setup lang="ts">
import { onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { acknowledgeNotice, consentOpen, openNoticeIfUnacknowledged } from '../../composables/useCookieConsent'

const page = usePage<PageProps>()
const { t } = useTranslation()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

onMounted(openNoticeIfUnacknowledged)
</script>

<template>
    <div
        v-if="consentOpen"
        role="dialog"
        aria-live="polite"
        :aria-label="t('cookie_notice')"
        class="fixed inset-x-0 bottom-0 z-[9998] border-t border-stone-200 bg-white/95 px-4 py-5 shadow-2xl backdrop-blur dark:border-stone-800 dark:bg-stone-950/95"
    >
        <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                {{ t('cookie_notice') }}
                <Link :href="localeUrl('/privacy')" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
                    {{ t('privacy_policy') }}
                </Link>
            </p>
            <button
                type="button"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-950"
                @click="acknowledgeNotice"
            >
                {{ t('cookie_acknowledge') }}
            </button>
        </div>
    </div>
</template>
