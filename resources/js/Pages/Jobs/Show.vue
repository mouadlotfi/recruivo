<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import type { PageProps, JobDetail, JobSummary } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import ExpandedTextarea from '../../Components/Applications/ExpandedTextarea.vue'
import JobCard from '../../Components/Jobs/JobCard.vue'

const props = defineProps<{
    job: JobDetail
    similarJobs: JobSummary[]
    canApply: boolean
    hasApplied: boolean
    isDemoCandidate: boolean
    hasProfileResume: boolean
    applicationSubmissionToken: string | null
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const user = computed(() => page.props.auth.user)
const isAuthenticated = computed(() => user.value !== null)

const companyInitial = computed(() => (props.job.company ? props.job.company.name.charAt(0) : 'J'))

const salaryRange = computed(() => {
    const { salary_min: min, salary_max: max } = props.job
    if (!min && !max) return null
    return `$${Number(min ?? 0).toLocaleString()} - $${Number(max ?? 0).toLocaleString()}`
})

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

const searchUrl = computed(() => localeUrl('/search'))
const applyUrl = computed(() => localeUrl(`/jobs/${props.job.id}/apply`))
const saveUrl = computed(() => localeUrl(`/candidate/saved-jobs/${props.job.id}`))
const companyUrl = computed(() => (props.job.company ? localeUrl(`/companies/${props.job.company.slug}`) : null))

// Single-use session token prevents duplicate application submissions.
const form = useForm<{
    resume_source: 'profile' | 'upload'
    resume: File | null
    cover_letter: string
    submission_token: string
}>({
    resume_source: props.hasProfileResume ? 'profile' : 'upload',
    resume: null,
    cover_letter: '',
    submission_token: props.applicationSubmissionToken ?? '',
})

const resumeSource = computed({
    get: () => form.resume_source,
    set: (value: 'profile' | 'upload') => {
        form.resume_source = value
        if (value === 'profile') form.resume = null
    },
})

const submitApplication = () => {
    form.post(applyUrl.value, {
        forceFormData: true,
        onSuccess: () => form.reset(),
    })
}

const toggleSaved = () => {
    if (props.job.is_saved) {
        router.delete(saveUrl.value, { preserveState: true, preserveScroll: true })
    } else {
        router.post(saveUrl.value, {}, { preserveState: true, preserveScroll: true })
    }
}
</script>

<template>
    <Head :title="job.title">
        <meta name="description" :content="labels.meta_description">
    </Head>

    <AppLayout>
        <div class="space-y-8">
            <Link
                :href="localeUrl('/jobs')"
                class="inline-flex items-center gap-2 text-sm font-medium text-stone-600 transition hover:text-amber-600 dark:text-stone-400 dark:hover:text-amber-400"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ labels.back_to_jobs }}
            </Link>

            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="sr-only">{{ labels.job_details }}</h2>

                    <div class="space-y-6">
                        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <div class="flex items-start gap-4 border-b border-stone-200 pb-6 dark:border-stone-700">
                            <img
                                v-if="job.company?.logo_url"
                                :src="job.company.logo_url"
                                :alt="job.company.name"
                                class="h-16 w-16 rounded-lg object-cover"
                            >
                            <div
                                v-else
                                class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-xl font-semibold text-white"
                            >
                                {{ companyInitial }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h1 class="text-2xl font-bold text-stone-900 dark:text-white">{{ job.title }}</h1>
                                <Link
                                    v-if="companyUrl"
                                    :href="companyUrl"
                                    class="text-lg text-amber-600 hover:text-amber-500 dark:text-amber-400"
                                >
                                    {{ job.company?.name }}
                                </Link>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    v-if="job.location"
                                    data-job-location-link
                                    :href="searchUrl"
                                    :data="{ location: job.location, filter: 'jobs' }"
                                    class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-sm text-stone-700 transition hover:bg-amber-100 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
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
                                    class="rounded-full px-3 py-1 text-sm font-medium transition"
                                    :class="remoteTypeClass"
                                >
                                    {{ remoteTypeLabel }}
                                </Link>
                                <Link
                                    v-if="job.category"
                                    :href="searchUrl"
                                    :data="{ search: job.category }"
                                    class="rounded-full bg-teal-100 px-3 py-1 text-sm font-medium text-teal-700 transition hover:bg-teal-200 dark:bg-teal-500/10 dark:text-teal-300 dark:hover:bg-teal-500/20"
                                >
                                    {{ job.category }}
                                </Link>
                            </div>

                            <div v-if="salaryRange" class="flex items-center gap-2 text-lg font-semibold text-stone-900 dark:text-white">
                                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ salaryRange }}
                            </div>

                            <!-- description_html is the trusted output of
                                 App\Support\JobDescriptionFormatter (escapes every
                                 user-controlled line) — the ONLY v-html on the page. -->
                            <div class="prose prose-stone max-w-none dark:prose-invert mt-6 job-description" v-html="job.description_html"></div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <template v-if="!isAuthenticated">
                            <Link
                                :href="localeUrl('/login')"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                            >
                                {{ labels.log_in_to_apply }}
                            </Link>
                        </template>

                        <div
                            v-else-if="isDemoCandidate"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            {{ labels.demo_cannot_apply }}
                        </div>

                        <div
                            v-else-if="canApply && hasApplied"
                            class="rounded-lg bg-green-50 p-4 text-sm text-green-600 dark:bg-green-900/20 dark:text-green-400"
                        >
                            <div class="flex items-center">
                                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ labels.you_have_applied }}
                            </div>
                        </div>

                        <form v-else-if="canApply" class="space-y-4" @submit.prevent="submitApplication">
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label for="cover_letter" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        {{ labels.cover_letter }}
                                    </label>
                                    <ExpandedTextarea
                                        v-model="form.cover_letter"
                                        :title="labels.write_cover_letter"
                                        :expand-label="labels.write_cover_letter"
                                        :cancel-label="labels.cancel"
                                        :done-label="labels.done"
                                        :close-label="labels.close"
                                        :placeholder="labels.cover_letter_placeholder"
                                    />
                                </div>
                                <textarea
                                    id="cover_letter"
                                    v-model="form.cover_letter"
                                    name="cover_letter"
                                    rows="4"
                                    required
                                    :placeholder="labels.cover_letter_placeholder"
                                    class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:border-amber-400 dark:focus:ring-amber-800/50"
                                ></textarea>
                                <p v-if="form.errors.cover_letter" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.cover_letter }}</p>
                            </div>

                            <fieldset class="space-y-3">
                                <legend class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.resume_source }}</legend>
                                <label
                                    v-if="hasProfileResume"
                                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-stone-200 px-3 py-2 text-sm dark:border-stone-700"
                                >
                                    <input v-model="resumeSource" type="radio" name="resume_source" value="profile">
                                    <span>{{ labels.use_profile_resume }}</span>
                                </label>
                                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-stone-200 px-3 py-2 text-sm dark:border-stone-700">
                                    <input v-model="resumeSource" type="radio" name="resume_source" value="upload">
                                    <span>{{ labels.upload_application_resume }}</span>
                                </label>
                                <p v-if="form.errors.resume_source" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.resume_source }}</p>
                            </fieldset>

                            <div v-if="resumeSource === 'upload'">
                                <label for="application_resume" class="mb-2 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    {{ labels.application_resume }}
                                </label>
                                <input
                                    id="application_resume"
                                    type="file"
                                    accept=".pdf,.doc,.docx"
                                    :required="resumeSource === 'upload'"
                                    aria-describedby="application-resume-help"
                                    class="block w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-amber-100 file:px-3 file:py-2 file:font-semibold file:text-amber-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:file:bg-amber-500/10 dark:file:text-amber-300"
                                    @input="form.resume = ($event.target as HTMLInputElement).files?.[0] ?? null"
                                >
                                <p id="application-resume-help" class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ labels.application_resume_help }}</p>
                                <p v-if="form.errors.resume" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.resume }}</p>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                :aria-busy="form.processing"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 disabled:opacity-60"
                            >
                                {{ form.processing ? labels.loading : labels.apply_now_button }}
                            </button>
                        </form>

                        <div
                            v-else
                            class="rounded-lg bg-blue-50 p-4 text-sm text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                        >
                            {{ labels.only_candidates_can_apply }}
                        </div>

                        <div class="mt-4 text-xs text-stone-500 dark:text-stone-400">{{ job.posted_label }}</div>
                        <div
                            v-if="job.is_closing_soon"
                            class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            {{ labels.closing_soon }}<template v-if="job.closes_label"> · {{ job.closes_label }}</template>
                        </div>
                    </div>

                    <div v-if="isAuthenticated && (canApply || isDemoCandidate)" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <div v-if="isDemoCandidate" class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-stone-300 dark:text-stone-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                </svg>
                            </span>
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ labels.demo_cannot_save_jobs }}</p>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-6 py-3 text-base font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-200"
                            :class="job.is_saved
                                ? 'border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20'
                                : 'bg-stone-100 text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700'"
                            @click="toggleSaved"
                        >
                            <svg class="h-5 w-5" :fill="job.is_saved ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                            </svg>
                            {{ job.is_saved ? labels.remove_saved_job : labels.save_job }}
                        </button>
                    </div>

                    <div v-if="job.company" class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <h3 class="mb-4 text-sm font-semibold text-stone-900 dark:text-white">{{ labels.about_company }}</h3>
                        <p v-if="job.company.tagline" class="mb-4 text-sm text-stone-600 dark:text-stone-400">{{ job.company.tagline }}</p>
                        <Link
                            :href="companyUrl ?? '#'"
                            class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400"
                        >
                            {{ labels.view_company_profile }}
                        </Link>
                    </div>
                </div>
            </div>

            <div v-if="similarJobs.length" class="mt-12">
                <h2 class="mb-6 text-2xl font-bold text-stone-900 dark:text-white">{{ labels.similar_jobs_title }}</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <JobCard v-for="similarJob in similarJobs" :key="similarJob.id" :job="similarJob" :labels="labels" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Port of the Blade .job-description style block (recruiter/jobs/show) for
   the v-html formatter output. */
.job-description h3 {
    margin: 1.25rem 0 0.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1c1917;
}
.job-description h3:first-child {
    margin-top: 0;
}
.job-description p {
    margin: 0.5rem 0;
}
.job-description ul {
    margin: 0.25rem 0 0.5rem;
    padding-left: 1.25rem;
    list-style: disc;
}
.job-description li {
    margin: 0.25rem 0;
}
.dark .job-description h3 {
    color: #f5f5f4;
}
</style>
