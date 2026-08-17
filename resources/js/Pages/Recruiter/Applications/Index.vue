<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, Pagination, RecruiterApplication, RecruiterNoteTemplate, StatusCount } from '../../../types'
import AppLayout from '../../../Layouts/AppLayout.vue'
import RecruiterApplicationCard from '../../../Components/Applications/RecruiterApplicationCard.vue'

const props = defineProps<{
    job: {
        id: number
        title: string
    }
    status: string
    statusCounts: StatusCount[]
    applications: RecruiterApplication[]
    noteTemplates: RecruiterNoteTemplate[]
    pagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const jobApplicationsUrl = computed(() => localeUrl(`/recruiter/jobs/${props.job.id}/applications`))

const TAB_KEYS = ['all', 'pending', 'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn'] as const

const tabs = computed(() =>
    TAB_KEYS.map((key) => ({
        key,
        label: key === 'all' ? props.labels.all_statuses : props.labels[key],
        count: props.statusCounts.find((c) => c.key === key)?.count ?? 0,
    })),
)

const manageTemplatesUrl = computed(() =>
    localeUrl(`/recruiter/note-templates?back=${encodeURIComponent(window.location.href)}`),
)

// "Show more" visits next_page_url with preserveState: Inertia swaps
// `applications` for the fresh page's items, so keep a local list and append
// whatever ids we don't already have.
const items = ref<RecruiterApplication[]>([...props.applications])
watch(
    () => props.applications,
    (incoming) => {
        const known = new Set(items.value.map((a) => a.id))
        const fresh = incoming.filter((a) => !known.has(a.id))
        if (fresh.length) items.value = [...items.value, ...fresh]
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

const subtitle = computed(() =>
    props.status === 'all' ? props.labels.applications_received : props.labels.filtered_applications_received,
)
</script>

<template>
    <AppLayout>
        <div class="space-y-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ labels.applications_for }}</h1>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ subtitle }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-stretch gap-2 self-start sm:flex-row sm:items-center">
                    <Link
                        :href="manageTemplatesUrl"
                        class="inline-flex items-center justify-center self-start whitespace-nowrap rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                    >
                        {{ labels.manage_templates }}
                    </Link>
                    <Link
                        :href="localeUrl('/recruiter/jobs')"
                        class="inline-flex items-center justify-center self-start whitespace-nowrap rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        {{ labels.back_to_jobs_list }}
                    </Link>
                </div>
            </div>

            <nav
                data-application-status-tabs
                :aria-label="props.labels.filter_applications"
                class="flex gap-2 overflow-x-auto border-b border-stone-200 pb-3 dark:border-stone-800"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="tab.key === 'all' ? jobApplicationsUrl : `${jobApplicationsUrl}?status=${tab.key}`"
                    :aria-current="props.status === tab.key ? 'page' : undefined"
                    :class="[
                        'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition',
                        props.status === tab.key
                            ? 'bg-amber-600 text-white shadow-sm'
                            : 'bg-stone-100 text-stone-600 hover:bg-stone-200 hover:text-stone-900 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white',
                    ]"
                >
                    {{ tab.label }}
                    <span
                        :class="[
                            'rounded-full px-2 py-0.5 text-xs',
                            props.status === tab.key ? 'bg-white/20 text-white' : 'bg-white text-stone-500 dark:bg-stone-800 dark:text-stone-400',
                        ]"
                    >{{ tab.count }}</span>
                </Link>
            </nav>

            <div
                v-if="items.length === 0"
                class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60"
            >
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                    <svg class="h-8 w-8 text-stone-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                </div>
                <h2 class="text-lg font-semibold text-stone-900 dark:text-white">
                    {{ props.status === 'all' ? labels.no_applications_received : labels.no_applications_with_status }}
                </h2>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    {{ props.status === 'all' ? labels.applications_appear_message : labels.no_applications_with_status_message }}
                </p>
            </div>

            <template v-else>
                <div class="space-y-4" data-infinite-items>
                    <RecruiterApplicationCard
                        v-for="application in items"
                        :key="application.id"
                        :application="application"
                        :note-templates="noteTemplates"
                        :labels="labels"
                    />
                </div>

                <div v-if="hasMore" class="mt-8 flex min-h-12 items-center justify-center">
                    <p v-if="loadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-stone-950"
                        @click="loadMore"
                    >
                        {{ loadingMore ? labels.loading_more : labels.show_more }}
                    </button>
                </div>
            </template>
        </div>
    </AppLayout>
</template>