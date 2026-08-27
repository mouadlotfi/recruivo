<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { CompanyCardSummary, PageProps } from '../../types'

// Card uses a stretched link overlay (before:absolute); interactive elements require relative z-10.
const props = defineProps<{
    company: CompanyCardSummary
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const companyUrl = computed(() => localeUrl(`/companies/${props.company.slug}`))
const searchUrl = computed(() => localeUrl('/search'))
const initial = computed(() => props.company.name.charAt(0))
const jobsCountLabel = computed(() => `${props.company.jobs_count} ${props.company.jobs_count === 1 ? 'job' : 'jobs'}`)
</script>

<template>
    <div class="group relative rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
        <div class="flex items-start gap-4">
            <div v-if="company.logo_url" class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg">
                <img
                    :src="company.logo_url"
                    :alt="company.name"
                    class="h-full w-full object-cover"
                >
            </div>
            <div
                v-else
                class="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-xl font-semibold text-white"
            >
                {{ initial }}
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                    <Link
                        :href="companyUrl"
                        class="before:absolute before:inset-0 before:rounded-xl focus:outline-none focus-visible:before:ring-2 focus-visible:before:ring-amber-500 focus-visible:before:ring-offset-2 dark:focus-visible:before:ring-offset-stone-950"
                    >
                        {{ company.name }}
                    </Link>
                </h3>
                <p v-if="company.tagline" class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ company.tagline }}</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                    <Link
                        v-if="company.location"
                        data-company-location-link
                        :href="searchUrl"
                        :data="{ location: company.location, filter: 'jobs' }"
                        class="relative z-10 flex items-center gap-1 rounded-md transition hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:hover:text-amber-400"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        {{ company.location }}
                    </Link>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        {{ jobsCountLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>