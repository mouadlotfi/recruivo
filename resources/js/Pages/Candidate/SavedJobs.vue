<script setup lang="ts">
import { computed } from 'vue'
import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/vue3'
import type { JobSummary, PageProps, ScrollPagination } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import JobCard from '../../Components/Jobs/JobCard.vue'

const props = defineProps<{
    jobs: ScrollPagination<JobSummary>
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

function removeSavedJob(jobId: number): void {
    const index = props.jobs.data.findIndex((job) => job.id === jobId)
    if (index !== -1) {
        props.jobs.data.splice(index, 1)
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.saved_jobs" />

        <div class="space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ labels.saved_jobs }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.saved_jobs_empty_description }}</p>
                </div>
                <Link :href="localeUrl('/jobs')" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 sm:px-6 sm:py-3">
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    {{ labels.browse_jobs }}
                </Link>
            </div>

            <InfiniteScroll data="jobs" manual only-next>
                <template #default>
                    <div v-if="jobs.data.length === 0" class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10"><svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg></div>
                        <h2 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_saved_jobs_yet }}</h2>
                        <p class="mb-6 text-stone-600 dark:text-stone-400">{{ labels.saved_jobs_empty_description }}</p>
                        <Link :href="localeUrl('/jobs')" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200">{{ labels.browse_jobs }}</Link>
                    </div>

                    <div v-else class="space-y-4" data-infinite-items>
                        <JobCard v-for="job in jobs.data" :key="job.id" :job="job" :labels="labels" @bookmark-removed="removeSavedJob" />
                    </div>
                </template>

                <template #next="{ hasMore, loading, fetch }">
                    <div v-if="hasMore && jobs.data.length" class="mt-8 flex min-h-12 items-center justify-center">
                        <button
                            type="button"
                            :disabled="loading"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:cursor-wait disabled:opacity-70 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                            @click="fetch"
                        >
                            {{ loading ? labels.loading_more : labels.show_more }}
                        </button>
                    </div>
                </template>
            </InfiniteScroll>
        </div>
    </AppLayout>
</template>
