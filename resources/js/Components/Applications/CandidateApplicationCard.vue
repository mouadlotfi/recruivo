<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { PageProps, CandidateApplication } from '../../types'
import ApplicationStatusBadge from './ApplicationStatusBadge.vue'
import StatusTimeline from './StatusTimeline.vue'
import CoverLetterDisclosure from './CoverLetterDisclosure.vue'

const props = defineProps<{
    application: CandidateApplication
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
const withdrawUrl = `/${page.props.locale}/candidate/applications/${props.application.id}/withdraw`
const jobUrl = `/${page.props.locale}/jobs/${props.application.job.id}`

const canWithdraw = computed(() => ['pending', 'shortlisted', 'interview'].includes(props.application.status))

const companyInitial = computed(() =>
    props.application.job.company ? props.application.job.company.name.charAt(0) : 'J',
)

const remoteTypeLabel = computed(() => {
    const type = props.application.job.remote_type
    return type ? type.charAt(0).toUpperCase() + type.slice(1) : null
})

const salaryRange = computed(() => {
    const { salary_min: min, salary_max: max } = props.application.job
    if (!min || !max) return null
    return `$${Number(min).toLocaleString()} - $${Number(max).toLocaleString()}`
})

const interviewModeLabel = computed(() =>
    props.application.interview?.mode === 'online' ? props.labels.interview_online : props.labels.interview_onsite,
)

const confirmWithdraw = (event: Event) => {
    if (!window.confirm(props.labels.withdraw_confirm)) {
        event.preventDefault()
    }
}
</script>

<template>
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur transition hover:border-amber-200 dark:border-stone-700/60 dark:bg-stone-900/60 dark:hover:border-amber-800 sm:p-6">
        <details data-application-card-collapsible class="group">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-4 [&::-webkit-details-marker]:hidden">
                <div class="flex min-w-0 flex-1 basis-full items-center gap-4 sm:basis-auto">
                    <img
                        v-if="application.job.company?.logo_url"
                        :src="application.job.company.logo_url"
                        :alt="application.job.company.name"
                        class="h-12 w-12 shrink-0 rounded-lg object-cover"
                    >
                    <div
                        v-else
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                    >
                        {{ companyInitial }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-xl font-semibold leading-snug text-stone-900 dark:text-white">
                            {{ application.job.title }}
                        </h3>
                        <p v-if="application.job.company" class="mt-0.5 text-sm text-stone-600 dark:text-stone-400">
                            {{ application.job.company.name }}
                        </p>
                    </div>
                </div>

                <ApplicationStatusBadge :status="application.status" :label="application.status_label" />

                <svg class="h-4 w-4 shrink-0 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </summary>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex-1">
                    <div class="mb-3 flex flex-wrap items-center gap-4 text-sm text-stone-600 dark:text-stone-400">
                        <div v-if="application.job.location" class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            {{ application.job.location }}
                        </div>
                        <span v-if="remoteTypeLabel" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ remoteTypeLabel }}
                        </span>
                        <span v-if="application.job.category" class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">
                            {{ application.job.category }}
                        </span>
                    </div>

                    <div class="mb-3 text-sm text-stone-500 dark:text-stone-500">
                        {{ application.applied_label }}
                    </div>

                    <CoverLetterDisclosure
                        v-if="application.cover_letter"
                        :content="application.cover_letter"
                        :label="props.labels.your_cover_letter"
                    />

                    <div v-if="application.notes" class="mb-3">
                        <h4 class="mb-2 text-sm font-medium text-stone-700 dark:text-stone-300">{{ props.labels.recruiter_notes }}</h4>
                        <div class="rounded-lg bg-blue-50 p-4 text-sm text-stone-700 dark:bg-blue-500/10 dark:text-stone-300">
                            {{ application.notes }}
                        </div>
                    </div>

                    <section v-if="application.timeline.length" class="mb-3 mt-4" :aria-labelledby="`status-timeline-${application.id}`">
                        <h4 :id="`status-timeline-${application.id}`" class="mb-2 text-sm font-semibold text-stone-700 dark:text-stone-300">
                            {{ props.labels.status_timeline }}
                        </h4>
                        <StatusTimeline :events="application.timeline" />
                    </section>

                    <div
                        v-if="application.interview"
                        class="mb-3 rounded-lg border border-violet-200 bg-violet-50 p-4 text-sm text-stone-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-stone-300"
                    >
                        <h4 class="mb-2 text-sm font-medium text-stone-700 dark:text-stone-300">{{ props.labels.interview_scheduled }}</h4>
                        <p v-if="application.interview.formatted_at">
                            <strong>{{ props.labels.interview_when }}</strong> {{ application.interview.formatted_at }}
                        </p>
                        <p><strong>{{ interviewModeLabel }}</strong></p>
                        <p v-if="application.interview.location">
                            <strong>{{ props.labels.interview_where }}</strong> {{ application.interview.location }}
                        </p>
                        <p v-if="application.interview.url">
                            <strong>{{ props.labels.interview_link }}</strong>
                            <a
                                :href="application.interview.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-amber-700 hover:underline dark:text-amber-400"
                            >{{ application.interview.url }}</a>
                        </p>
                        <p v-if="application.interview.instructions" class="mt-2 whitespace-pre-line">{{ application.interview.instructions }}</p>
                    </div>

                    <div v-if="salaryRange" class="text-sm">
                        <span class="font-medium text-stone-700 dark:text-stone-300">{{ props.labels.salary }}</span>
                        <span class="text-stone-600 dark:text-stone-400">{{ salaryRange }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:ml-6 sm:items-start">
                    <Link
                        :href="jobUrl"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:w-auto"
                    >
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ props.labels.view_job }}
                    </Link>
                    <form v-if="canWithdraw" method="POST" :action="withdrawUrl" @submit="confirmWithdraw">
                        <input type="hidden" name="_token" :value="csrfToken" />
                        <input type="hidden" name="_method" value="PATCH" />
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10 sm:w-auto">
                            {{ props.labels.withdraw_application }}
                        </button>
                    </form>
                </div>
            </div>
        </details>
    </div>
</template>
