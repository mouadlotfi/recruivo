<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, CandidateApplication, Pagination, StatusCount } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import CandidateApplicationCard from '../../Components/Applications/CandidateApplicationCard.vue'

const props = defineProps<{
    applications: CandidateApplication[]
    status: string
    statusCounts: StatusCount[]
    pagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const TAB_KEYS = ['all', 'pending', 'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn'] as const

const tabs = computed(() =>
    TAB_KEYS.map((key) => ({
        key,
        label: props.labels[key],
        count: props.statusCounts.find((c) => c.key === key)?.count ?? 0,
    })),
)

// Merges pagination chunks in local state while preserving real-time status updates.
const items = ref<CandidateApplication[]>([...props.applications])
watch(
    () => [props.status, props.pagination.current_page, props.applications] as const,
    ([status, currentPage, incoming], [previousStatus]) => {
        if (status !== previousStatus) {
            items.value = [...incoming]
            return
        }

        const pageStart = Math.max(0, (currentPage - 1) * props.pagination.per_page)
        const beforePage = items.value.slice(0, pageStart)
        const incomingIds = new Set(incoming.map((application) => application.id))
        const afterPage = items.value
            .slice(pageStart + props.pagination.per_page)
            .filter((application) => !incomingIds.has(application.id))

        items.value = [...beforePage, ...incoming, ...afterPage]
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
</script>

<template>
    <AppLayout>
        <div class="space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ props.labels.my_applications }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ props.labels.subtitle }}</p>
                </div>
                <Link
                    :href="localeUrl('/jobs')"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    {{ props.labels.browse_jobs }}
                </Link>
            </div>

            <nav
                data-candidate-application-status-tabs
                :aria-label="props.labels.my_applications"
                class="flex w-full gap-1 overflow-x-auto rounded-xl border border-stone-200 bg-white p-1 dark:border-stone-700 dark:bg-stone-800 sm:w-fit"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="localeUrl(tab.key === 'all' ? '/candidate/applications' : `/candidate/applications?status=${tab.key}`)"
                    :aria-current="props.status === tab.key ? 'page' : undefined"
                    :class="[
                        'min-h-11 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                        props.status === tab.key
                            ? 'bg-amber-600 text-white shadow-sm'
                            : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white',
                    ]"
                >
                    {{ tab.label }} ({{ tab.count }})
                </Link>
            </nav>

            <div
                v-if="items.length === 0"
                class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60"
            >
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                    <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-stone-900 dark:text-white">
                    {{ props.status === 'all' ? props.labels.no_applications_yet : props.labels.no_applications_for_status }}
                </h3>
                <p class="mb-6 text-stone-600 dark:text-stone-400">{{ props.labels.start_applying }}</p>
                <Link
                    :href="localeUrl('/jobs')"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                >
                    {{ props.labels.browse_available_jobs }}
                </Link>
            </div>

            <template v-else>
                <div class="space-y-4">
                    <CandidateApplicationCard
                        v-for="application in items"
                        :key="application.id"
                        :application="application"
                        :labels="props.labels"
                    />
                </div>

                <div v-if="hasMore" class="mt-8 flex min-h-12 items-center justify-center">
                    <p v-if="loadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ props.labels.load_more_failed }}</p>
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-stone-950"
                        @click="loadMore"
                    >
                        {{ loadingMore ? props.labels.loading_more : props.labels.show_more }}
                    </button>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
