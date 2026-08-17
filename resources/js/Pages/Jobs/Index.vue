<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps, JobFilters, JobSummary, Pagination } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import JobCard from '../../Components/Jobs/JobCard.vue'

const props = defineProps<{
    jobs: JobSummary[]
    hasPreferences: boolean
    filters: JobFilters
    pagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const isRecruiter = computed(() => page.props.auth.user?.roles.includes('Recruiter') ?? false)
const heading = computed(() => (isRecruiter.value ? props.labels.recruiter_explore_title : props.labels.find_opportunity))
const subtitle = computed(() => (isRecruiter.value ? props.labels.recruiter_explore_subtitle : props.labels.discover_jobs))

const filters = reactive<JobFilters>({ ...props.filters })

const applyFilters = () => {
    const params: Record<string, string> = {}
    for (const [key, value] of Object.entries(filters)) {
        if (value !== null && value !== '') params[key] = String(value)
    }
    router.get(localeUrl('/jobs'), params, { preserveState: true, preserveScroll: true })
}

const clearFilters = () => {
    filters.search = null
    filters.location = null
    filters.category = null
    filters.salary_min = null
    filters.salary_max = null
    router.get(localeUrl('/jobs'), {}, { preserveState: true, preserveScroll: true })
}

// "Show more" visits next_page_url with preserveState; Inertia swaps `jobs`
// for the fresh page's items, so keep a local list. A fresh query (filter
// submit / clear / back-forward) always lands on page 1 and replaces the
// list; load-more and bookmark-redirect visits merge by id so toggled
// is_saved/has_applied states refresh in place.
const items = ref<JobSummary[]>([...props.jobs])
watch(
    () => props.jobs,
    (incoming) => {
        if (props.pagination.current_page === 1) {
            items.value = [...incoming]
            Object.assign(filters, props.filters)
            return
        }
        const byId = new Map(items.value.map((job) => [job.id, job]))
        for (const job of incoming) byId.set(job.id, job)
        items.value = [...byId.values()]
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

const inputClass = 'w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20'
</script>

<template>
    <AppLayout>
        <Head :title="heading" />
        <div class="space-y-8">
            <header>
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ heading }}</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">{{ subtitle }}</p>
            </header>


            <div>
                <h2 v-if="hasPreferences" class="mb-4 text-xl font-semibold text-stone-900 dark:text-white">
                    {{ labels.recommended_for_you }}
                </h2>

                <div v-if="items.length" class="grid gap-4 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3" data-infinite-items>
                    <JobCard v-for="job in items" :key="job.id" :job="job" :labels="labels" />
                </div>

                <div
                    v-else
                    class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40"
                >
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_jobs_found }}</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.check_back_later }}</p>
                    <div class="mt-4">
                        <Link
                            :href="localeUrl('/')"
                            class="inline-flex items-center justify-center rounded-full border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:border-amber-400 hover:text-amber-500 dark:border-amber-500/40 dark:text-amber-300 dark:hover:border-amber-400/60"
                        >
                            {{ labels.back_to_home }}
                        </Link>
                    </div>
                </div>

                <div v-if="hasMore && items.length" class="mt-6 text-center">
                    <p v-if="loadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                    <button
                        type="button"
                        :disabled="loadingMore"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                        @click="loadMore"
                    >
                        {{ loadingMore ? labels.loading_more : labels.show_more }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
