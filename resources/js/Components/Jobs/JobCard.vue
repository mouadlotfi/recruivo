<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, JobSummary } from '../../types'

// Card uses a stretched link overlay (before:absolute); interactive elements require relative z-10.
const props = defineProps<{
    job: JobSummary
    labels: Record<string, string>
}>()

const emit = defineEmits<{
    bookmarkRemoved: [jobId: number]
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const user = computed(() => page.props.auth.user)
const isCandidate = computed(() => user.value?.roles.includes('Candidate') ?? false)
const isDemoCandidate = computed(() => isCandidate.value && (user.value?.is_demo ?? false))

const jobUrl = computed(() => localeUrl(`/jobs/${props.job.id}`))
const searchUrl = computed(() => localeUrl('/search'))

const companyInitial = computed(() => (props.job.company ? props.job.company.name.charAt(0) : 'J'))

// Design-system badge map: hybrid=green, onsite=orange, remote=purple,
// unknown falls back to amber.
const REMOTE_TYPE_STYLES: Record<string, string> = {
    hybrid: 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-500/10 dark:text-green-300 dark:hover:bg-green-500/20',
    onsite: 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:hover:bg-orange-500/20',
    remote: 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:hover:bg-purple-500/20',
}

const remoteTypeClass = computed(() => {
    const type = props.job.remote_type?.toLowerCase() ?? ''
    return REMOTE_TYPE_STYLES[type] ?? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20'
})

const remoteTypeLabel = computed(() => {
    const type = props.job.remote_type
    if (!type) return null
    return props.labels[type.toLowerCase()] ?? type.charAt(0).toUpperCase() + type.slice(1)
})

const salaryRange = computed(() => {
    const { salary_min: min, salary_max: max } = props.job
    if (!min && !max) return null
    return `$${Number(min ?? 0).toLocaleString()} - $${Number(max ?? 0).toLocaleString()}`
})

const bookmarkInFlight = ref(false)

const actionReserve = computed(() => {
    if (!isCandidate.value) return ''

    return props.job.has_applied ? 'pr-36' : 'pr-16'
})

const bookmarkReloadProps = computed(() => {
    switch (page.component) {
        case 'Home/Index':
        case 'Jobs/Index':
        case 'Search/Index':
            return ['jobs', 'flash']
        case 'Candidate/SavedJobs':
            return ['jobs', 'pagination', 'flash']
        case 'Companies/Show':
            return ['company', 'flash']
        case 'Jobs/Show':
            return ['job', 'similarJobs', 'flash']
        default:
            return ['jobs', 'flash']
    }
})

function toggleSaved(): void {
    if (isDemoCandidate.value || bookmarkInFlight.value) return

    bookmarkInFlight.value = true
    const removing = props.job.is_saved
    const url = localeUrl(`/candidate/saved-jobs/${props.job.id}`)
    const options = {
        only: bookmarkReloadProps.value,
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
        onSuccess: () => {
            if (removing) emit('bookmarkRemoved', props.job.id)
        },
        onFinish: () => {
            bookmarkInFlight.value = false
        },
    }

    if (props.job.is_saved) {
        router.delete(url, options)
    } else {
        router.post(url, {}, options)
    }
}
</script>

<template>
    <div class="group relative h-full rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-4" :class="actionReserve">
                    <div v-if="job.company?.logo_url" class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl">
                        <img
                            :src="job.company.logo_url"
                            :alt="job.company.name"
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div
                        v-else
                        class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-lg font-semibold text-white"
                    >
                        {{ companyInitial }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                            <Link :href="jobUrl" class="before:absolute before:inset-0">
                                {{ job.title }}
                            </Link>
                        </h3>
                        <p v-if="job.company" class="text-sm text-stone-600 dark:text-stone-400">{{ job.company.name }}</p>
                    </div>
                </div>

                <div v-if="isCandidate" class="absolute right-3 top-3 z-10 flex items-center gap-2">
                    <div
                        v-if="job.has_applied"
                        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400"
                    >
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        {{ labels.applied }}
                    </div>
                    <button
                        v-if="isDemoCandidate"
                        type="button"
                        disabled
                        :title="labels.demo_cannot_save_jobs"
                        :aria-label="labels.demo_cannot_save_jobs"
                        class="relative z-10 inline-flex h-11 w-11 cursor-not-allowed items-center justify-center rounded-full text-stone-300 dark:text-stone-600"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                    </button>
                    <button
                        v-else
                        type="button"
                        :disabled="bookmarkInFlight"
                        :aria-busy="bookmarkInFlight"
                        :aria-label="job.is_saved ? labels.remove_saved_job : labels.save_job"
                        :title="job.is_saved ? labels.remove_saved_job : labels.save_job"
                        class="relative z-10 inline-flex h-11 w-11 items-center justify-center rounded-full transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:cursor-wait disabled:opacity-60"
                        :class="job.is_saved
                            ? 'text-amber-600 hover:bg-stone-100 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-stone-800'
                            : 'text-stone-400 hover:bg-stone-100 hover:text-amber-600 dark:hover:bg-stone-800'"
                        @click="toggleSaved"
                    >
                        <svg class="h-5 w-5" :fill="job.is_saved ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" :stroke="job.is_saved ? 'none' : 'currentColor'" aria-hidden="true">
                            <path v-if="job.is_saved" fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                    <Link
                        v-if="job.location"
                        data-job-location-link
                        :href="searchUrl"
                        :data="{ location: job.location, filter: 'jobs' }"
                        class="relative z-10 flex items-center gap-1 rounded-md transition hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:hover:text-amber-400"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        {{ job.location }}
                    </Link>
                    <Link
                        v-if="job.remote_type"
                        :href="searchUrl"
                        :data="{ search: '', remote_type: job.remote_type.toLowerCase() }"
                        class="relative z-10 inline-flex w-fit max-w-full rounded-full px-2 py-0.5 font-medium transition"
                        :class="remoteTypeClass"
                    >
                        {{ remoteTypeLabel }}
                    </Link>
                    <Link
                        v-if="job.category"
                        :href="searchUrl"
                        :data="{ search: job.category }"
                        class="relative z-10 inline-flex w-fit max-w-full rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 transition hover:bg-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20"
                    >
                        {{ job.category }}
                    </Link>
            </div>

            <p v-if="salaryRange" class="text-sm font-medium text-stone-700 dark:text-stone-300">
                {{ salaryRange }}
            </p>

            <p
                v-if="job.is_closing_soon"
                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
            >
                {{ labels.closing_soon }}<template v-if="job.closes_label"> · {{ job.closes_label }}</template>
            </p>
        </div>
    </div>
</template>
