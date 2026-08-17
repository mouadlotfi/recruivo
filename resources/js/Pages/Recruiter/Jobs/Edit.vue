<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { PageProps, RecruiterJobDetail } from '../../../types'
import AppLayout from '../../../Layouts/AppLayout.vue'
import JobForm from '../../../Components/Recruiter/JobForm.vue'

defineProps<{
    job: RecruiterJobDetail
    categories: string[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`
</script>

<template>
    <Head :title="job.title" />

    <AppLayout>
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ labels.edit_job_title }}</h1>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.edit_job_subtitle }}</p>
                </div>
                <Link
                    :href="localeUrl('/recruiter/jobs')"
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-stone-100 px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ labels.back_to_jobs }}
                </Link>
            </div>

            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <JobForm mode="edit" :job="job" :categories="categories" :labels="labels" />
            </div>
        </div>
    </AppLayout>
</template>
