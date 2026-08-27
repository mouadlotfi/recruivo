<script setup lang="ts">
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import AdminSidebar from './AdminSidebar.vue'
import UserDropdown from '../Layout/UserDropdown.vue'
import ThemeToggle from '../Layout/ThemeToggle.vue'
import FlashMessages from '../Layout/FlashMessages.vue'

const props = defineProps<{
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

const sidebarOpen = ref(false)
</script>

<template>
    <div class="flex h-screen w-full overflow-hidden bg-[#f4f4f5] text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
        <AdminSidebar 
            :labels="props.labels" 
            :mobile-open="sidebarOpen" 
            @close-mobile="sidebarOpen = false" 
        />

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-stone-200/60 bg-white/95 px-4 shadow-sm backdrop-blur-sm sm:px-6 lg:px-8 dark:border-stone-800/70 dark:bg-stone-900/95 dark:shadow-none">
                <div class="flex items-center gap-4">
                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-stone-500 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-500 lg:hidden dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-white"
                        @click="sidebarOpen = true"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <!-- Mobile Logo -->
                    <Link :href="localeUrl('/admin/dashboard')" class="flex items-center gap-2 lg:hidden">
                        <span class="inline-flex h-8 w-8 items-center justify-center">
                            <svg viewBox="0 0 48 48" fill="none" class="h-8 w-8" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="header-logo-amber" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#F59E0B" />
                                        <stop offset="100%" stop-color="#D97706" />
                                    </linearGradient>
                                    <linearGradient id="header-logo-teal" x1="100%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#14B8A6" />
                                        <stop offset="100%" stop-color="#0F766E" />
                                    </linearGradient>
                                </defs>
                                <path d="M8 24C8 15.163 15.163 8 24 8" stroke="url(#header-logo-amber)" stroke-width="6" stroke-linecap="round" />
                                <path d="M40 24C40 32.837 32.837 40 24 40" stroke="url(#header-logo-teal)" stroke-width="6" stroke-linecap="round" />
                                <circle cx="24" cy="24" r="5" fill="url(#header-logo-amber)" />
                            </svg>
                        </span>
                        <span class="font-display text-lg font-semibold tracking-tight text-stone-900 dark:text-white">
                            Recruivo
                        </span>
                    </Link>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <ThemeToggle />
                    <UserDropdown />
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <FlashMessages />
                <div class="mx-auto max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

