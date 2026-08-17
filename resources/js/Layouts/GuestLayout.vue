<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'
import { useTranslation } from '../composables/useTranslation'
import Navigation from '../Components/Layout/Navigation.vue'
import NotificationCenter from '../Components/Layout/NotificationCenter.vue'
import UserDropdown from '../Components/Layout/UserDropdown.vue'
import ThemeToggle from '../Components/Layout/ThemeToggle.vue'
import ScrollToTop from '../Components/Layout/ScrollToTop.vue'
import FlashMessages from '../Components/Layout/FlashMessages.vue'

const page = usePage<PageProps>()
const { t } = useTranslation()
const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const logoHref = computed(() => localeUrl(isRecruiter.value ? '/recruiter/dashboard' : '/'))
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
</script>

<template>
    <div class="min-h-screen flex flex-col bg-stone-50 text-stone-900 antialiased transition-colors duration-300 dark:bg-stone-950 dark:text-stone-100">
        <a href="#main-content" class="sr-only z-[100] rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ t('skip_to_content') }}</a>
        <div class="relative isolate flex-1 overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10">
                <div class="mx-auto h-72 max-w-5xl rounded-full bg-gradient-to-r from-amber-400/20 via-teal-400/10 to-stone-400/15 blur-3xl"></div>
                <div class="absolute -bottom-20 right-10 h-48 w-48 rounded-full bg-amber-300/20 blur-2xl dark:bg-amber-500/10"></div>
            </div>

            <header class="sticky top-0 z-[9999] border-b border-stone-200/60 bg-white/75 backdrop-blur-xl dark:border-stone-800/70 dark:bg-stone-950/80">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <div class="flex h-full items-center gap-6 lg:gap-8">
                        <Link :href="logoHref" class="group flex items-center gap-2.5 text-lg font-semibold">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center transition group-hover:scale-105">
                                <svg viewBox="0 0 48 48" fill="none" class="h-9 w-9" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <defs><linearGradient id="guest-logo-amber" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/></linearGradient><linearGradient id="guest-logo-teal" x1="100%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#14B8A6"/><stop offset="100%" stop-color="#0F766E"/></linearGradient></defs>
                                    <path d="M8 24C8 15.163 15.163 8 24 8" stroke="url(#guest-logo-amber)" stroke-width="6" stroke-linecap="round"/><path d="M40 24C40 32.837 32.837 40 24 40" stroke="url(#guest-logo-teal)" stroke-width="6" stroke-linecap="round"/><circle cx="24" cy="24" r="5" fill="url(#guest-logo-amber)"/>
                                </svg>
                            </span>
                            <span class="font-semibold tracking-tight text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">Recruivo</span>
                        </Link>
                        <Navigation />
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <Link id="mobile-search-toggle" :href="localeUrl('/search')" :aria-label="t('search')" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg><span class="sr-only">{{ t('search') }}</span>
                        </Link>
                        <template v-if="user">
                            <Link v-if="isRecruiter" :href="localeUrl('/recruiter/jobs/create')" class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 xl:inline-flex dark:hover:bg-amber-500/90 dark:focus-visible:ring-offset-stone-950">{{ t('post_job') }}</Link>
                            <template v-if="!isAdmin"><NotificationCenter /><UserDropdown /></template>
                            <form v-else method="POST" :action="localeUrl('/logout')"><input type="hidden" name="_token" :value="csrfToken" /><button type="submit" class="inline-flex h-9 items-center gap-2 rounded-full px-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-200 dark:hover:bg-stone-800"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg><span class="hidden sm:inline">{{ t('sign_out') }}</span></button></form>
                        </template>
                        <template v-else>
                            <Link :href="localeUrl('/login')" class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full border border-stone-200/80 px-4 text-sm font-semibold text-stone-600 transition hover:border-amber-300 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:inline-flex dark:border-stone-700 dark:text-stone-200 dark:hover:border-amber-400 dark:hover:text-amber-300">{{ t('log_in') }}</Link>
                            <Link :href="localeUrl('/register')" class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 sm:inline-flex dark:hover:bg-amber-500/90 dark:focus-visible:ring-offset-stone-950">{{ t('sign_up') }}</Link>
                        </template>
                        <ThemeToggle />
                    </div>
                </div>
            </header>

            <main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 pb-20 pt-10 sm:px-6 sm:pb-16">
                <FlashMessages />
                <slot />
            </main>
        </div>
        <footer class="border-t border-stone-200 bg-white/70 py-6 text-sm text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-950/80 dark:text-stone-400"><div class="mx-auto flex max-w-6xl flex-col items-center justify-center gap-3 px-4 text-center sm:flex-row sm:justify-between sm:gap-2 sm:px-6 sm:text-left"><p class="max-w-md">{{ t('footer_text', { year: new Date().getFullYear() }) }}</p><div class="flex items-center gap-4 whitespace-nowrap"><a href="https://mouadlotfi.com" class="transition hover:text-amber-600 dark:hover:text-amber-400" target="_blank" rel="noopener noreferrer">Portfolio</a><a href="https://www.linkedin.com/in/mouad-lotfi/" class="transition hover:text-amber-600 dark:hover:text-amber-400" target="_blank" rel="noopener noreferrer">LinkedIn</a><a href="mailto:mouad.lotfi.work@gmail.com" class="transition hover:text-amber-600 dark:hover:text-amber-400">{{ t('contact') }}</a></div></div></footer>
        <ScrollToTop />
    </div>
</template>
