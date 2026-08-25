<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, Pagination, RecruiterJobSummary } from '../../../types'
import AppLayout from '../../../Layouts/AppLayout.vue'

const props = defineProps<{
    jobs: RecruiterJobSummary[]
    pagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

// Buckets loaded jobs by page number to reconcile status changes without losing pagination state.
const pageItems = ref(new Map<number, RecruiterJobSummary[]>([
    [props.pagination.current_page, [...props.jobs]],
]))
const items = ref<RecruiterJobSummary[]>([])

const reconcileItems = () => {
    const seen = new Set<number>()
    items.value = [...pageItems.value.entries()]
        .sort(([leftPage], [rightPage]) => leftPage - rightPage)
        .flatMap(([, jobs]) => jobs.filter((job) => {
            if (seen.has(job.id)) return false
            seen.add(job.id)
            return true
        }))
}

reconcileItems()

watch(
    [() => props.jobs, () => props.pagination.current_page],
    ([incoming, currentPage]) => {
        pageItems.value.set(currentPage, [...incoming])
        reconcileItems()
    },
)

const hasMore = computed(() => props.pagination.next_page_url !== null)
const loadingMore = ref(false)
const loadMoreFailed = ref(false)

const loadMore = () => {
    const url = props.pagination.next_page_url
    if (!url || loadingMore.value) return
    loadingMore.value = true
    loadMoreFailed.value = false
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            loadMoreFailed.value = true
        },
        onFinish: () => {
            loadingMore.value = false
        },
    })
}

const jobShowUrl = (job: RecruiterJobSummary) => localeUrl(`/recruiter/jobs/${job.id}`)
const jobApplicationsUrl = (job: RecruiterJobSummary) => localeUrl(`/recruiter/jobs/${job.id}/applications`)
const jobEditUrl = (job: RecruiterJobSummary) => localeUrl(`/recruiter/jobs/${job.id}/edit`)

const selectedJob = ref<RecruiterJobSummary | null>(null)
const deleteDialog = ref<HTMLElement | null>(null)
const deleteCancelButton = ref<HTMLButtonElement | null>(null)
const focusableSelector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

const closeDeleteDialog = () => {
    selectedJob.value = null
}

const focusDeleteDialog = () => {
    nextTick(() => deleteCancelButton.value?.focus())
}

watch(selectedJob, (job) => {
    if (job) focusDeleteDialog()
})

const handleDialogKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return

    const focusable = deleteDialog.value
        ? Array.from(deleteDialog.value.querySelectorAll<HTMLElement>(focusableSelector))
        : []
    if (focusable.length === 0) {
        event.preventDefault()
        return
    }

    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
    }
}

const handleWindowKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && selectedJob.value) {
        event.preventDefault()
        closeDeleteDialog()
    }
}

onMounted(() => window.addEventListener('keydown', handleWindowKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', handleWindowKeydown))

const deleteJob = () => {
    if (!selectedJob.value) return
    router.delete(jobShowUrl(selectedJob.value), {
        preserveScroll: true,
        onFinish: () => { selectedJob.value = null },
    })
}

const toggleJob = (job: RecruiterJobSummary) => {
    router.post(localeUrl(`/recruiter/jobs/${job.id}/toggle`), {}, { preserveScroll: true })
}

const toggleTitle = (job: RecruiterJobSummary) =>
    job.is_expired
        ? props.labels.extend_closing_date_before_publishing
        : job.status === 'published'
            ? props.labels.unpublish
            : props.labels.publish

const badge = (job: RecruiterJobSummary) => {
    if (job.is_expired) {
        return { label: props.labels.expired, cls: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }
    }
    if (job.status === 'published') {
        return { label: props.labels.published, cls: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' }
    }
    return { label: props.labels.draft, cls: 'bg-stone-100 text-stone-700 dark:bg-stone-500/10 dark:text-stone-400' }
}

const iconButtonClass =
    'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-40'
</script>

<template>
    <Head :title="labels.my_job_listings" />

    <AppLayout>
        <div class="space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ labels.my_job_listings }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.my_job_listings_subtitle }}</p>
                </div>
                <Link
                    :href="localeUrl('/recruiter/jobs/create')"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">{{ labels.post_new_job }}</span>
                    <span class="sm:hidden">{{ labels.post_new_job }}</span>
                </Link>
            </div>

            <div
                v-if="items.length === 0"
                class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60"
            >
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                    <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_job_listings }}</h3>
                <p class="mb-6 text-stone-600 dark:text-stone-400">{{ labels.get_started_posting }}</p>
                <Link
                    :href="localeUrl('/recruiter/jobs/create')"
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200"
                >
                    {{ labels.post_first_job }}
                </Link>
            </div>

            <template v-else>
                <div class="space-y-4">
                    <article
                        v-for="job in items"
                        :key="job.id"
                        data-recruiter-job-card
                        class="group relative rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur transition hover:border-amber-300 hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60 dark:hover:border-amber-700 sm:p-6"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="mb-3 flex min-w-0 flex-col items-start gap-2 sm:flex-row sm:items-center">
                                    <h3 class="min-w-0 break-words text-xl font-semibold text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                                        <Link
                                            :href="jobShowUrl(job)"
                                            class="before:absolute before:inset-0 focus:outline-none focus-visible:before:rounded-xl focus-visible:before:ring-2 focus-visible:before:ring-amber-500 focus-visible:before:ring-offset-2 dark:focus-visible:before:ring-offset-stone-950"
                                        >
                                            {{ job.title }}
                                        </Link>
                                    </h3>
                                    <span
                                        class="inline-flex shrink-0 whitespace-normal rounded-full px-3 py-1 text-xs font-medium"
                                        :class="badge(job).cls"
                                    >
                                        <svg v-if="job.status === 'published' || job.status === 'draft'" class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8" aria-hidden="true">
                                            <circle cx="4" cy="4" r="3" />
                                        </svg>
                                        {{ badge(job).label }}
                                    </span>
                                </div>

                                <div class="mb-3 flex min-w-0 flex-wrap items-center gap-4 text-sm text-stone-600 dark:text-stone-400">
                                    <div v-if="job.location" class="flex min-w-0 items-start gap-1 break-words">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                        <span class="break-words">{{ job.location }}</span>
                                    </div>
                                    <span v-if="job.remote_type" class="break-words rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ labels[job.remote_type.replace('-', '').toLowerCase()] || job.remote_type }}</span>
                                    <span v-if="job.category" class="break-words rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">{{ job.category }}</span>
                                </div>

                                <div data-job-metadata class="grid min-w-0 gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center">
                                    <div class="flex min-w-0 items-center gap-2 break-words">
                                        <svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        <span class="break-words text-stone-600 dark:text-stone-400">{{ job.applications_label }}</span>
                                    </div>
                                    <div class="min-w-0 break-words text-stone-500 dark:text-stone-500">{{ job.posted_label }}</div>
                                    <div v-if="job.published_label" class="min-w-0 break-words text-stone-500 dark:text-stone-500">{{ job.published_label }}</div>
                                    <div v-if="job.closes_label" class="min-w-0 break-words text-stone-500 dark:text-stone-500">{{ job.closes_label }}</div>
                                </div>
                            </div>

                            <div data-job-actions class="relative z-10 flex flex-wrap items-center gap-2 sm:ml-4">
                                <Link
                                    :href="jobApplicationsUrl(job)"
                                    :title="labels.view_applications_title"
                                    :aria-label="labels.view_applications_title"
                                    :class="[iconButtonClass, 'bg-stone-100 text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700']"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </Link>

                                <Link
                                    :href="jobEditUrl(job)"
                                    :title="labels.edit_job_title_attr"
                                    :aria-label="labels.edit_job_title_attr"
                                    :class="[iconButtonClass, 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20']"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </Link>

                                <button
                                    type="button"
                                    :disabled="job.is_expired"
                                    :title="toggleTitle(job)"
                                    :aria-label="toggleTitle(job)"
                                    :class="[
                                        iconButtonClass,
                                        job.status === 'published'
                                            ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20'
                                            : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20',
                                    ]"
                                    @click="toggleJob(job)"
                                >
                                    <svg v-if="job.status === 'published'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    :title="labels.delete_job_title"
                                    :aria-label="labels.delete_job_title"
                                    :class="[iconButtonClass, 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20']"
                                    @click="selectedJob = job"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="hasMore" class="mt-8 flex min-h-12 items-center justify-center">
                    <p v-if="loadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                        @click="loadMore"
                    >
                        {{ loadingMore ? labels.loading_more : labels.show_more }}
                    </button>
                </div>
            </template>
        </div>

        <div v-if="selectedJob" ref="deleteDialog" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" :aria-labelledby="`delete-job-title-${selectedJob.id}`" :aria-describedby="`delete-job-description-${selectedJob.id}`" @keydown="handleDialogKeydown">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm" @click="closeDeleteDialog"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="p-6 sm:p-8"><div class="flex items-start"><div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20"><svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div></div><div class="mt-4 text-center"><h3 :id="`delete-job-title-${selectedJob.id}`" class="text-xl font-semibold text-stone-900 dark:text-white">{{ labels.delete_job_title }}</h3><div class="mt-3"><p :id="`delete-job-description-${selectedJob.id}`" class="text-sm text-stone-600 dark:text-stone-400">{{ labels.delete_job_confirm }}</p></div></div></div>
                    <div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8"><button type="button" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto" @click="deleteJob">{{ labels.delete_job }}</button><button ref="deleteCancelButton" type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto" @click="closeDeleteDialog">{{ labels.cancel }}</button></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
