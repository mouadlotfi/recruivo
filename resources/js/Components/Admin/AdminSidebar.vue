<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

const props = defineProps<{
    labels: Record<string, string>
    mobileOpen?: boolean
}>()

const emit = defineEmits<{
    (e: 'close-mobile'): void
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

const isActive = (href: string) => {
    const currentPath = page.url.split('?')[0]
    return currentPath === href || currentPath.startsWith(`${href}/`)
}

const linkClasses = (href: string) => [
    'flex min-h-[44px] items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500',
    isActive(href)
        ? 'bg-stone-900 text-white shadow-sm dark:bg-amber-500/20 dark:text-amber-300'
        : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-800/80 dark:hover:text-white',
]
</script>

<template>
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-[100] flex w-64 flex-col border-r border-stone-200/70 bg-white transition-transform duration-300 ease-in-out dark:border-stone-800 dark:bg-stone-950 lg:static lg:translate-x-0',
            mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full',
        ]"
        :aria-label="labels.admin_area"
    >
        <!-- Logo Area -->
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-stone-200/70 px-6 dark:border-stone-800">
            <Link :href="localeUrl('/admin/dashboard')" class="group flex items-center gap-2.5 text-lg font-semibold" @click="emit('close-mobile')">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center transition group-hover:scale-105">
                    <svg viewBox="0 0 48 48" fill="none" class="h-8 w-8" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logo-amber" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#F59E0B" />
                                <stop offset="100%" stop-color="#D97706" />
                            </linearGradient>
                            <linearGradient id="logo-teal" x1="100%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#14B8A6" />
                                <stop offset="100%" stop-color="#0F766E" />
                            </linearGradient>
                        </defs>
                        <path d="M8 24C8 15.163 15.163 8 24 8" stroke="url(#logo-amber)" stroke-width="6" stroke-linecap="round" />
                        <path d="M40 24C40 32.837 32.837 40 24 40" stroke="url(#logo-teal)" stroke-width="6" stroke-linecap="round" />
                        <circle cx="24" cy="24" r="5" fill="url(#logo-amber)" />
                    </svg>
                </span>
                <span class="font-display tracking-tight text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                    Recruivo
                </span>
                <span
                    v-if="page.props.isDemoEnvironment"
                    class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30"
                >
                    DEMO
                </span>
            </Link>
            <button type="button" class="lg:hidden text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200" @click="emit('close-mobile')">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            <nav class="flex flex-col space-y-1" :aria-label="labels.admin_area">
                <p class="px-3 pb-2 pt-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-500">
                    {{ labels.sidebar_overview }}
                </p>
                <Link
                    :href="localeUrl('/admin/dashboard')"
                    :class="linkClasses(localeUrl('/admin/dashboard'))"
                    :aria-current="isActive(localeUrl('/admin/dashboard')) ? 'page' : undefined"
                    @click="emit('close-mobile')"
                >
                    <svg class="h-5 w-5 shrink-0 opacity-75" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A1.5 1.5 0 015.25 3.75h5.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-5.5a1.5 1.5 0 01-1.5-1.5v-5.5zM11.75 13.75a1.5 1.5 0 011.5-1.5h5.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-5.5a1.5 1.5 0 01-1.5-1.5v-5.5zM3.75 13.75a1.5 1.5 0 011.5-1.5h2.5a1.5 1.5 0 011.5 1.5v5.5a1.5 1.5 0 01-1.5 1.5h-2.5a1.5 1.5 0 01-1.5-1.5v-5.5zM13.75 3.75a1.5 1.5 0 011.5-1.5h2.5a1.5 1.5 0 011.5 1.5v2.5a1.5 1.5 0 01-1.5 1.5h-2.5a1.5 1.5 0 01-1.5-1.5v-2.5z" />
                    </svg>
                    <span class="whitespace-nowrap">{{ labels.sidebar_overview }}</span>
                </Link>

                <p class="px-3 pb-2 pt-6 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-500">
                    {{ labels.sidebar_management }}
                </p>
                <Link
                    :href="localeUrl('/admin/users')"
                    :class="linkClasses(localeUrl('/admin/users'))"
                    :aria-current="isActive(localeUrl('/admin/users')) ? 'page' : undefined"
                    @click="emit('close-mobile')"
                >
                    <svg class="h-5 w-5 shrink-0 opacity-75" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="whitespace-nowrap">{{ labels.sidebar_users }}</span>
                </Link>
                <Link
                    :href="localeUrl('/admin/jobs')"
                    :class="linkClasses(localeUrl('/admin/jobs'))"
                    :aria-current="isActive(localeUrl('/admin/jobs')) ? 'page' : undefined"
                    @click="emit('close-mobile')"
                >
                    <svg class="h-5 w-5 shrink-0 opacity-75" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5h-16.5A1.5 1.5 0 002.25 9v9.75a1.5 1.5 0 001.5 1.5h16.5a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 00-1.5-1.5zM8.25 7.5V6A2.25 2.25 0 0110.5 3.75h3A2.25 2.25 0 0115.75 6v1.5M2.25 12h19.5" />
                    </svg>
                    <span class="whitespace-nowrap">{{ labels.sidebar_jobs }}</span>
                </Link>
            </nav>
        </div>
    </aside>

    <!-- Mobile Overlay -->
    <div 
        v-if="mobileOpen" 
        class="fixed inset-0 z-[90] bg-stone-900/50 backdrop-blur-sm transition-opacity lg:hidden"
        @click="emit('close-mobile')"
        aria-hidden="true"
    ></div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: transparent;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: rgba(168, 162, 158, 0.4);
}
</style>
