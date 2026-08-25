<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { useDismiss } from '../../composables/useDismiss'

const page = usePage<PageProps>()
const { t } = useTranslation()

const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isCandidate = computed(() => user.value?.roles.includes('Candidate') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)

const localeUrl = (path: string) => `/${page.props.locale}${path}`
// Match pathname prefix so active states persist during filter or query changes.
const isActive = (href: string) => {
    const path = page.url.split('?')[0]
    return path === href || path.startsWith(`${href}/`)
}

const itemClass = 'flex min-w-0 flex-col items-center justify-center rounded-lg px-1 py-2 transition'
const activeClass = 'text-amber-600 dark:text-amber-400'
const inactiveClass = 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800'
const itemClasses = (href: string) => [itemClass, isActive(href) ? activeClass : inactiveClass]

const exploreOpen = ref(false)
const recruiterExploreRoot = ref<HTMLElement | null>(null)
const candidateExploreRoot = ref<HTMLElement | null>(null)
// Separate template refs per role branch to avoid Vue duplicate-ref warnings.
useDismiss(exploreOpen, recruiterExploreRoot)
useDismiss(exploreOpen, candidateExploreRoot)
const exploreActive = computed(() => isActive(localeUrl('/jobs')) || isActive(localeUrl('/companies')))

const exploreButtonClass = [itemClass, 'w-full']
const exploreItemClass = 'block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300'
const explorePanelClass = 'absolute bottom-full right-0 mb-2 w-max min-w-36 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 text-left shadow-xl dark:border-stone-700 dark:bg-stone-900'
</script>

<template>
    <nav
        :aria-label="t('primary_navigation')"
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-stone-200 bg-white/95 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/95 sm:hidden"
    >
        <div class="grid grid-cols-4 gap-1 px-2 py-2">
            <template v-if="!user">
                <Link :href="localeUrl('/')" :class="itemClasses(localeUrl('/'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l9-9 9 9M4.5 9.75V21h5.25v-6h4.5v6h5.25V9.75" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ t('home') }}</span>
                </Link>
                <Link :href="localeUrl('/jobs')" :class="itemClasses(localeUrl('/jobs'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25v1.5M3.75 9h16.5v9.75A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V9z" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ t('jobs') }}</span>
                </Link>
                <Link :href="localeUrl('/companies')" :class="itemClasses(localeUrl('/companies'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V3h10.5v18m0-13.5h6V21M7.5 7.5h3m-3 4h3m-3 4h3" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ t('companies') }}</span>
                </Link>
                <Link :href="localeUrl('/login')" :class="itemClasses(localeUrl('/login'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ t('log_in') }}</span>
                </Link>
            </template>

            <template v-else-if="isRecruiter">
                <Link :href="localeUrl('/recruiter/dashboard')" :class="itemClasses(localeUrl('/recruiter/dashboard'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('dashboard') }}</span>
                </Link>
                <Link :href="localeUrl('/recruiter/jobs')" :class="itemClasses(localeUrl('/recruiter/jobs'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h10.5M6.75 12h10.5M6.75 17.25h10.5" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('manage') }}</span>
                </Link>
                <div ref="recruiterExploreRoot" class="relative" data-recruiter-mobile-explore-menu>
                    <button
                        type="button"
                        aria-haspopup="menu"
                        :aria-expanded="exploreOpen"
                        :class="[exploreButtonClass, exploreActive ? activeClass : inactiveClass]"
                        @click="exploreOpen = !exploreOpen"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
                        <span class="mt-1 text-[11px] font-medium">{{ t('explore') }}</span>
                    </button>
                    <div v-if="exploreOpen" role="menu" :class="explorePanelClass">
                        <Link :href="localeUrl('/jobs')" role="menuitem" :class="exploreItemClass">{{ t('jobs') }}</Link>
                        <Link :href="localeUrl('/companies')" role="menuitem" :class="exploreItemClass">{{ t('companies') }}</Link>
                    </div>
                </div>
                <Link :href="localeUrl('/profile')" :class="itemClasses(localeUrl('/profile'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('settings') }}</span>
                </Link>
            </template>

            <template v-else-if="isCandidate">
                <Link :href="localeUrl('/candidate/dashboard')" :class="itemClasses(localeUrl('/candidate/dashboard'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('dashboard') }}</span>
                </Link>
                <div ref="candidateExploreRoot" class="relative" data-candidate-mobile-explore-menu>
                    <button
                        type="button"
                        aria-haspopup="menu"
                        :aria-expanded="exploreOpen"
                        :class="[exploreButtonClass, exploreActive ? activeClass : inactiveClass]"
                        @click="exploreOpen = !exploreOpen"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
                        <span class="mt-1 text-[11px] font-medium">{{ t('explore') }}</span>
                    </button>
                    <div v-if="exploreOpen" role="menu" :class="explorePanelClass">
                        <Link :href="localeUrl('/jobs')" role="menuitem" :class="exploreItemClass">{{ t('jobs') }}</Link>
                        <Link :href="localeUrl('/companies')" role="menuitem" :class="exploreItemClass">{{ t('companies') }}</Link>
                    </div>
                </div>
                <Link :href="localeUrl('/candidate/saved-jobs')" :class="itemClasses(localeUrl('/candidate/saved-jobs'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('saved_jobs_short') }}</span>
                </Link>
                <Link :href="localeUrl('/candidate/applications')" :class="itemClasses(localeUrl('/candidate/applications'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12A2.25 2.25 0 0117.25 20.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('my_applications_short') }}</span>
                </Link>
            </template>

            <template v-else-if="isAdmin">
                <Link :href="localeUrl('/admin/dashboard')" :class="itemClasses(localeUrl('/admin/dashboard'))">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ t('dashboard') }}</span>
                </Link>
            </template>
        </div>
    </nav>
</template>
