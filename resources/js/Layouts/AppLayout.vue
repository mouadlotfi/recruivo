<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'
import { useTranslation } from '../composables/useTranslation'
import Navigation from '../Components/Layout/Navigation.vue'
import SearchModal from '../Components/Layout/SearchModal.vue'
import MobileNav from '../Components/Layout/MobileNav.vue'
import UserDropdown from '../Components/Layout/UserDropdown.vue'
import NotificationCenter from '../Components/Layout/NotificationCenter.vue'
import ThemeToggle from '../Components/Layout/ThemeToggle.vue'
import LanguageToggle from '../Components/Layout/LanguageToggle.vue'
import ScrollToTop from '../Components/Layout/ScrollToTop.vue'
import FlashMessages from '../Components/Layout/FlashMessages.vue'

const page = usePage<PageProps>()
const { t } = useTranslation()

const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)

const localeUrl = (path: string) => `/${page.props.locale}${path}`
const logoHref = computed(() => {
    if (isAdmin.value) return localeUrl('/admin/dashboard')
    if (isRecruiter.value) return localeUrl('/recruiter/dashboard')

    return localeUrl('/')
})

const searchOpen = ref(false)
const searchTrigger = ref<HTMLButtonElement | null>(null)

</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-100 pb-16 text-stone-900 antialiased sm:pb-0 dark:bg-stone-950 dark:text-stone-100">
        <header class="sticky top-0 z-[9999] border-b border-stone-200/60 bg-white/75 backdrop-blur-xl dark:border-stone-800/70 dark:bg-stone-950/80">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <div class="flex h-full items-center gap-6 lg:gap-8">
                    <Link :href="logoHref" class="group flex items-center gap-2.5 text-lg font-semibold">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center transition group-hover:scale-105">
                            <svg viewBox="0 0 48 48" fill="none" class="h-9 w-9" xmlns="http://www.w3.org/2000/svg">
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
                        <span class="font-semibold tracking-tight text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                            Recruivo
                        </span>
                        <span
                            v-if="page.props.isDemoEnvironment"
                            class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30"
                            :title="t('demo_environment_notice')"
                        >
                            {{ t('demo_environment_badge') }}
                        </span>
                    </Link>
                    <Navigation v-if="!isAdmin" />
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <button
                        v-if="!isAdmin"
                        ref="searchTrigger"
                        id="mobile-search-toggle"
                        type="button"
                        aria-haspopup="dialog"
                        :aria-expanded="searchOpen"
                        :aria-label="t('search')"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-stone-100 dark:focus-visible:ring-offset-stone-950"
                        @click="searchOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <span class="sr-only">{{ t('search') }}</span>
                    </button>

                    <template v-if="user">
                        <Link
                            v-if="isRecruiter"
                            :href="localeUrl('/recruiter/jobs/create')"
                            class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 xl:inline-flex dark:hover:bg-amber-500/90 dark:focus-visible:ring-offset-stone-950"
                        >
                            {{ t('post_job') }}
                        </Link>
                        <NotificationCenter v-if="!isAdmin" />
                        <UserDropdown />
                    </template>
                    <template v-else>
                        <Link
                            :href="localeUrl('/login')"
                            class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full border border-stone-200/80 px-4 text-sm font-semibold text-stone-600 transition hover:border-amber-300 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:inline-flex dark:border-stone-700 dark:text-stone-200 dark:hover:border-amber-400 dark:hover:text-amber-300"
                        >
                            {{ t('log_in') }}
                        </Link>
                        <Link
                            :href="localeUrl('/register')"
                            class="hidden h-9 items-center justify-center whitespace-nowrap rounded-full bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 sm:inline-flex dark:hover:bg-amber-500/90 dark:focus-visible:ring-offset-stone-950"
                        >
                            {{ t('sign_up') }}
                        </Link>
                    </template>

                    <LanguageToggle />
                    <ThemeToggle />
                </div>
            </div>
        </header>

        <FlashMessages />

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-20 pt-10 sm:px-6 sm:pb-16">
            <slot />
        </main>

        <footer class="border-t border-stone-200 bg-white/70 py-6 text-sm text-stone-500 backdrop-blur dark:border-stone-800 dark:bg-stone-950/80 dark:text-stone-400">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-center gap-3 px-4 text-center sm:flex-row sm:justify-between sm:gap-2 sm:px-6 sm:text-left">
                <p class="max-w-md">{{ t('footer_text', { year: new Date().getFullYear() }) }}</p>
                <div class="flex items-center gap-4 whitespace-nowrap">
                    <a
                        href="https://mouadlotfi.com"
                        class="transition hover:text-amber-600 dark:hover:text-amber-400"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Portfolio
                    </a>
                    <a
                        href="https://www.linkedin.com/in/mouad-lotfi/"
                        class="transition hover:text-amber-600 dark:hover:text-amber-400"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        LinkedIn
                    </a>
                    <a href="mailto:mouad.lotfi.work@gmail.com" class="transition hover:text-amber-600 dark:hover:text-amber-400">
                        {{ t('contact') }}
                    </a>
                </div>
            </div>
        </footer>

        <MobileNav v-if="!isAdmin" />
        <ScrollToTop />
        <template v-if="!isAdmin">
            <SearchModal v-model:open="searchOpen" :trigger="searchTrigger" />
        </template>
    </div>
</template>
