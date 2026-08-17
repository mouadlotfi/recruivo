<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import { useDismiss } from '../../composables/useDismiss'

// Desktop nav — port of the <nav> block in resources/views/partials/header.blade.php.
// Role checks are UI-only gating; routes enforce server-side.
const page = usePage<PageProps>()
const { t } = useTranslation()

const user = computed(() => page.props.auth.user)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)
const isCandidate = computed(() => user.value?.roles.includes('Candidate') ?? false)
const isAdmin = computed(() => user.value?.roles.includes('Admin') ?? false)

const localeUrl = (path: string) => `/${page.props.locale}${path}`
// Compare against the pathname only so query strings (filters, pagination) don't kill active states.
const isActive = (href: string) => {
    const path = page.url.split('?')[0]
    return path === href || path.startsWith(`${href}/`)
}

const exploreOpen = ref(false)
const exploreRoot = ref<HTMLElement | null>(null)
useDismiss(exploreOpen, exploreRoot)
const exploreActive = computed(() => isActive(localeUrl('/jobs')) || isActive(localeUrl('/companies')))

const manageJobsHref = localeUrl('/recruiter/jobs')
const manageJobsActive = computed(() => isActive(manageJobsHref) && !page.url.split('?')[0].includes('/applications'))

const navLinkClass = (href: string, activeOverride?: boolean) => [
    'relative flex h-full items-center whitespace-nowrap px-3 text-sm font-medium transition hover:text-amber-600 dark:hover:text-amber-400',
    (activeOverride ?? isActive(href)) ? 'text-amber-600 dark:text-amber-400' : '',
]

const exploreItemClass = (href: string) => [
    'block px-4 py-2.5 text-sm transition hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-500/10 dark:hover:text-amber-300',
    isActive(href) ? 'text-amber-600 dark:text-amber-400' : 'text-stone-700 dark:text-stone-200',
]
</script>

<template>
    <nav
        :aria-label="t('primary_navigation')"
        class="hidden h-full items-center gap-1 text-sm font-medium text-stone-600 lg:flex lg:gap-1.5 dark:text-stone-300"
    >
        <template v-if="isRecruiter">
            <div ref="exploreRoot" class="relative flex h-full items-center" data-recruiter-explore-menu>
                <button
                    type="button"
                    aria-haspopup="menu"
                    :aria-expanded="exploreOpen"
                    class="relative flex h-full items-center gap-1 whitespace-nowrap px-3 text-sm font-medium transition hover:text-amber-600 dark:hover:text-amber-400"
                    :class="exploreActive ? 'text-amber-600 dark:text-amber-400' : ''"
                    @click="exploreOpen = !exploreOpen"
                >
                    {{ t('explore') }}
                    <svg class="h-4 w-4 transition-transform" :class="exploreOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25L12 15.75 4.5 8.25" />
                    </svg>
                    <span v-if="exploreActive" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
                </button>
                <div
                    v-if="exploreOpen"
                    role="menu"
                    class="absolute left-0 top-full mt-1 w-44 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-xl dark:border-stone-700 dark:bg-stone-900"
                >
                    <Link :href="localeUrl('/jobs')" role="menuitem" :class="exploreItemClass(localeUrl('/jobs'))">{{ t('jobs') }}</Link>
                    <Link :href="localeUrl('/companies')" role="menuitem" :class="exploreItemClass(localeUrl('/companies'))">{{ t('companies') }}</Link>
                </div>
            </div>
            <Link :href="localeUrl('/recruiter/dashboard')" :class="navLinkClass(localeUrl('/recruiter/dashboard'))">
                {{ t('dashboard') }}
                <span v-if="isActive(localeUrl('/recruiter/dashboard'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="manageJobsHref" :class="navLinkClass(manageJobsHref, manageJobsActive)">
                {{ t('manage_jobs') }}
                <span v-if="manageJobsActive" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
        </template>

        <template v-else-if="isCandidate">
            <Link :href="localeUrl('/candidate/dashboard')" :class="navLinkClass(localeUrl('/candidate/dashboard'))">
                {{ t('dashboard') }}
                <span v-if="isActive(localeUrl('/candidate/dashboard'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="localeUrl('/jobs')" :class="navLinkClass(localeUrl('/jobs'))">
                {{ t('jobs') }}
                <span v-if="isActive(localeUrl('/jobs'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="localeUrl('/companies')" :class="navLinkClass(localeUrl('/companies'))">
                {{ t('companies') }}
                <span v-if="isActive(localeUrl('/companies'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="localeUrl('/candidate/applications')" :class="navLinkClass(localeUrl('/candidate/applications'))">
                {{ t('my_applications') }}
                <span v-if="isActive(localeUrl('/candidate/applications'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="localeUrl('/candidate/saved-jobs')" :class="navLinkClass(localeUrl('/candidate/saved-jobs'))">
                {{ t('saved_jobs') }}
                <span v-if="isActive(localeUrl('/candidate/saved-jobs'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
        </template>

        <template v-else-if="isAdmin">
            <Link :href="localeUrl('/admin/dashboard')" :class="navLinkClass(localeUrl('/admin/dashboard'))">
                {{ t('dashboard') }}
                <span v-if="isActive(localeUrl('/admin/dashboard'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
        </template>

        <template v-else>
            <Link :href="localeUrl('/jobs')" :class="navLinkClass(localeUrl('/jobs'))">
                {{ t('jobs') }}
                <span v-if="isActive(localeUrl('/jobs'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
            <Link :href="localeUrl('/companies')" :class="navLinkClass(localeUrl('/companies'))">
                {{ t('companies') }}
                <span v-if="isActive(localeUrl('/companies'))" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
            </Link>
        </template>
    </nav>
</template>
