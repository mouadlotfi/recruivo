<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

const props = defineProps<{
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

const isActive = (href: string) => {
    const currentPath = page.url.split('?')[0]

    return currentPath === href || currentPath.startsWith(`${href}/`)
}

const linkClasses = (href: string) => [
    'flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500',
    isActive(href)
        ? 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300'
        : 'border border-transparent text-stone-600 hover:bg-stone-50 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white',
]
</script>

<template>
    <aside
        :aria-label="labels.admin_area"
        class="min-w-0 rounded-xl border border-stone-200/70 bg-white/80 p-3 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 lg:sticky lg:top-24"
    >
        <div class="hidden border-b border-stone-200/70 px-3 pb-4 dark:border-stone-800 lg:block">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-500">{{ labels.admin_area }}</p>
            <p class="mt-1 font-display text-lg font-semibold text-stone-900 dark:text-white">Recruivo</p>
        </div>

        <nav class="flex gap-2 overflow-x-auto lg:block lg:space-y-1 lg:overflow-visible" :aria-label="labels.admin_area">
            <p class="hidden px-3 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-500 lg:block">
                {{ labels.sidebar_overview }}
            </p>
            <Link
                :href="localeUrl('/admin/dashboard')"
                :class="linkClasses(localeUrl('/admin/dashboard'))"
                :aria-current="isActive(localeUrl('/admin/dashboard')) ? 'page' : undefined"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A1.5 1.5 0 015.25 3.75h5.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-5.5a1.5 1.5 0 01-1.5-1.5v-5.5zM11.75 13.75a1.5 1.5 0 011.5-1.5h5.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-5.5a1.5 1.5 0 01-1.5-1.5v-5.5zM3.75 13.75a1.5 1.5 0 011.5-1.5h2.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-2.5a1.5 1.5 0 01-1.5-1.5v-5.5zM13.75 3.75a1.5 1.5 0 011.5-1.5h2.5a1.5 1.5 0 011.5 1.5v2.5a1.5 1.5 0 01-1.5 1.5h-2.5a1.5 1.5 0 01-1.5-1.5v-2.5z" />
                </svg>
                <span class="whitespace-nowrap">{{ labels.sidebar_overview }}</span>
            </Link>

            <p class="hidden px-3 pb-2 pt-5 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-500 lg:block">
                {{ labels.sidebar_management }}
            </p>
            <Link
                :href="localeUrl('/admin/users')"
                :class="linkClasses(localeUrl('/admin/users'))"
                :aria-current="isActive(localeUrl('/admin/users')) ? 'page' : undefined"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <span class="whitespace-nowrap">{{ labels.sidebar_users }}</span>
            </Link>
            <Link
                :href="localeUrl('/admin/jobs')"
                :class="linkClasses(localeUrl('/admin/jobs'))"
                :aria-current="isActive(localeUrl('/admin/jobs')) ? 'page' : undefined"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5h-16.5A1.5 1.5 0 002.25 9v9.75a1.5 1.5 0 001.5 1.5h16.5a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 00-1.5-1.5zM8.25 7.5V6A2.25 2.25 0 0110.5 3.75h3A2.25 2.25 0 0115.75 6v1.5M2.25 12h19.5" />
                </svg>
                <span class="whitespace-nowrap">{{ labels.sidebar_jobs }}</span>
            </Link>
        </nav>
    </aside>
</template>
