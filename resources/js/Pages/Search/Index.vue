<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { CompanyCardSummary, JobSummary, PageProps, Pagination } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import JobCard from '../../Components/Jobs/JobCard.vue'
import CompanyCard from '../../Components/Companies/CompanyCard.vue'
import SearchAutocomplete from '../../Components/Search/Autocomplete.vue'

const props = defineProps<{
    searchQuery: string
    filter: string
    remoteType: string | null
    location: string | null
    jobs: JobSummary[]
    companies: CompanyCardSummary[]
    jobsCount: number
    companiesCount: number
    totalCount: number
    suggestedCorrection: string | null
    popularSearches: string[]
    locations: string[]
    jobsPagination: Pagination
    companiesPagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`
const searchUrl = localeUrl('/search')

const hasCriteria = computed(() => Boolean(props.searchQuery || props.remoteType || props.location))
const hasActiveFilters = computed(() => Boolean(props.location || props.remoteType))
const remoteTypeLabel = computed(() => (props.remoteType ? props.labels[props.remoteType] : ''))

// Query params for a link/submit: current search/filter/remote_type/location
// unless overridden; null/empty values are dropped (mirrors the Blade page,
// which always keeps the filter param).
const paramsFor = (overrides: Partial<Record<'search' | 'filter' | 'remote_type' | 'location', string | null>> = {}) => {
    const params: Record<string, string> = {}
    const search = 'search' in overrides ? overrides.search : props.searchQuery
    const filter = overrides.filter ?? props.filter
    const remoteType = 'remote_type' in overrides ? overrides.remote_type : props.remoteType
    const location = 'location' in overrides ? overrides.location : props.location
    if (search) params.search = search
    params.filter = filter
    if (remoteType) params.remote_type = remoteType
    if (location) params.location = location
    return params
}

// --- Search bar (live autocomplete via the shared SearchAutocomplete component) ---
const searchInput = ref(props.searchQuery)
watch(
    () => props.searchQuery,
    (query) => {
        searchInput.value = query
    },
)

const submitSearch = () => {
    router.get(searchUrl, paramsFor({ search: searchInput.value.trim() }), { preserveState: true, preserveScroll: true })
}

// --- Result-type tabs ---
const tabs = computed(() => [
    { key: 'all', label: props.labels.all, count: props.totalCount },
    { key: 'jobs', label: props.labels.jobs, count: props.jobsCount },
    { key: 'companies', label: props.labels.companies, count: props.companiesCount },
])

// --- Filter panel ---
const showFilters = ref(false)
const panelLocation = ref(props.location ?? '')
const panelRemoteType = ref(props.remoteType ?? '')
watch(
    () => props.location,
    (value) => {
        panelLocation.value = value ?? ''
    },
)
watch(
    () => props.remoteType,
    (value) => {
        panelRemoteType.value = value ?? ''
    },
)

const applyPanelFilters = () => {
    router.get(searchUrl, paramsFor({ location: panelLocation.value || null, remote_type: panelRemoteType.value || null }), {
        preserveState: true,
        preserveScroll: true,
    })
}

// --- Sections (blade semantics: a section shows when the CURRENT page has
// items; the empty state replaces the sections when none do) ---
const showJobsSection = computed(() => ['all', 'jobs'].includes(props.filter) && props.jobs.length > 0)
const showCompaniesSection = computed(() => ['all', 'companies'].includes(props.filter) && props.companies.length > 0)
const showNoResults = computed(() => {
    if (props.filter === 'jobs') return props.jobs.length === 0
    if (props.filter === 'companies') return props.companies.length === 0
    return props.jobs.length === 0 && props.companies.length === 0
})
const noResultsTitle = computed(() => {
    if (props.filter === 'jobs') return props.labels.no_jobs_match
    if (props.filter === 'companies') return props.labels.no_companies_match
    return props.labels.no_results_title
})

// --- Dual pagination. Each list requests only its own props while loading
// more, so a bookmark partial reload can always replace the jobs page even
// when the companies list has already loaded additional pages.
const jobItems = ref<JobSummary[]>([...props.jobs])
watch(
    () => props.jobs,
    (incoming) => {
        if (props.jobsPagination.current_page === 1) {
            jobItems.value = [...incoming]
            return
        }
        const byId = new Map(jobItems.value.map((job) => [job.id, job]))
        for (const job of incoming) byId.set(job.id, job)
        jobItems.value = [...byId.values()]
    },
)

const companyItems = ref<CompanyCardSummary[]>([...props.companies])
watch(
    () => props.companies,
    (incoming) => {
        if (props.companiesPagination.current_page === 1) {
            companyItems.value = [...incoming]
            return
        }
        const byId = new Map(companyItems.value.map((company) => [company.id, company]))
        for (const company of incoming) byId.set(company.id, company)
        companyItems.value = [...byId.values()]
    },
)

const jobsHasMore = computed(() => props.jobsPagination.next_page_url !== null)
const jobsLoadingMore = ref(false)
const jobsLoadMoreFailed = ref(false)
const loadMoreJobs = () => {
    const url = props.jobsPagination.next_page_url
    if (!url || jobsLoadingMore.value) return
    jobsLoadingMore.value = true
    jobsLoadMoreFailed.value = false
    router.get(url, {}, {
        only: ['jobs', 'jobsPagination'],
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            jobsLoadMoreFailed.value = true
        },
        onFinish: () => {
            jobsLoadingMore.value = false
        },
    })
}

const companiesHasMore = computed(() => props.companiesPagination.next_page_url !== null)
const companiesLoadingMore = ref(false)
const companiesLoadMoreFailed = ref(false)
const loadMoreCompanies = () => {
    const url = props.companiesPagination.next_page_url
    if (!url || companiesLoadingMore.value) return
    companiesLoadingMore.value = true
    companiesLoadMoreFailed.value = false
    router.get(url, {}, {
        only: ['companies', 'companiesPagination'],
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            companiesLoadMoreFailed.value = true
        },
        onFinish: () => {
            companiesLoadingMore.value = false
        },
    })
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.search" />
        <div class="space-y-6">
            <!-- Sticky compact search bar with live autocomplete -->
            <div class="search-container relative" data-search-surface="page">
                <SearchAutocomplete
                    v-model="searchInput"
                    :search-url="searchUrl"
                    input-id="page-search"
                    :labels="labels"
                    @submit="submitSearch"
                />
            </div>

            <template v-if="hasCriteria">
                <!-- Result-type tabs with counts -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <nav :aria-label="labels.filter" class="inline-flex w-full rounded-xl border border-stone-200 bg-white p-1 dark:border-stone-700 dark:bg-stone-800 sm:w-auto">
                        <Link
                            v-for="tab in tabs"
                            :key="tab.key"
                            :data-search-tab="tab.key"
                            :href="searchUrl"
                            :data="paramsFor({ filter: tab.key })"
                            :aria-current="props.filter === tab.key ? 'page' : undefined"
                            class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-sm font-medium transition sm:px-4"
                            :class="props.filter === tab.key
                                ? 'bg-amber-600 text-white shadow-sm'
                                : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white'"
                        >
                            {{ tab.label }} <span class="tabular-nums opacity-80">({{ tab.count }})</span>
                        </Link>
                    </nav>

                    <p class="text-sm text-stone-600 dark:text-stone-400" aria-live="polite">
                        <template v-if="props.searchQuery">
                            {{ labels.results_for_query.replace(':query', props.searchQuery) }}
                        </template>
                        <template v-else>
                            {{ labels.showing_results.replace(':count', String(totalCount)) }}
                        </template>
                    </p>
                </div>

                <!-- Suggested correction -->
                <Link
                    v-if="suggestedCorrection"
                    :href="searchUrl"
                    :data="paramsFor({ search: suggestedCorrection })"
                    class="inline-flex rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                >
                    {{ labels.did_you_mean.replace(':query', suggestedCorrection) }}
                </Link>

                <!-- Active filter chips + collapsible filter panel -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-if="props.location"
                            data-active-filter-chip
                            :href="searchUrl"
                            :data="paramsFor({ location: null })"
                            :aria-label="labels.remove_filter.replace(':filter', labels.filter_location)"
                            class="group inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 py-1.5 pl-3 pr-2 text-xs font-medium text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                        >
                            <span><span class="font-semibold">{{ labels.filter_location }}:</span> {{ props.location }}</span>
                            <svg class="h-3.5 w-3.5 text-amber-500 transition group-hover:text-amber-700 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </Link>
                        <Link
                            v-if="props.remoteType"
                            data-active-filter-chip
                            :href="searchUrl"
                            :data="paramsFor({ remote_type: null })"
                            :aria-label="labels.remove_filter.replace(':filter', labels.filter_work_type)"
                            class="group inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 py-1.5 pl-3 pr-2 text-xs font-medium text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                        >
                            <span><span class="font-semibold">{{ labels.filter_work_type }}:</span> {{ remoteTypeLabel }}</span>
                            <svg class="h-3.5 w-3.5 text-amber-500 transition group-hover:text-amber-700 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </Link>

                        <button
                            type="button"
                            :aria-expanded="showFilters"
                            aria-controls="search-filter-panel"
                            class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                            @click="showFilters = !showFilters"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                            {{ labels.refine_filters }}
                            <svg class="h-3.5 w-3.5 transition-transform" :class="showFilters && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25L12 15.75 4.5 8.25" /></svg>
                        </button>
                    </div>

                    <div v-show="showFilters" id="search-filter-panel" class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-6">
                        <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyPanelFilters">
                            <div class="space-y-2">
                                <label for="location" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                        {{ labels.location }}
                                    </span>
                                </label>
                                <select
                                    id="location"
                                    v-model="panelLocation"
                                    data-search-location-filter
                                    class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2.5 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                                >
                                    <option value="">{{ labels.all_locations }}</option>
                                    <option v-for="availableLocation in locations" :key="availableLocation" :value="availableLocation">{{ availableLocation }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label for="remote_type" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    <span class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819" /></svg>
                                        {{ labels.work_type }}
                                    </span>
                                </label>
                                <select
                                    id="remote_type"
                                    v-model="panelRemoteType"
                                    class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2.5 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                                >
                                    <option value="">{{ labels.all_types }}</option>
                                    <option value="remote">{{ labels.remote }}</option>
                                    <option value="hybrid">{{ labels.hybrid }}</option>
                                    <option value="onsite">{{ labels.onsite }}</option>
                                </select>
                            </div>

                            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                                <button
                                    type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                >
                                    {{ labels.apply_filters }}
                                </button>
                                <Link
                                    :href="searchUrl"
                                    :data="paramsFor({ location: null, remote_type: null })"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                                >
                                    {{ labels.clear_filters }}
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- No-results empty state -->
                <div v-if="showNoResults" class="rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-12 text-center dark:border-stone-700 dark:bg-stone-900/40">
                    <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">{{ noResultsTitle }}</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.no_results_message }}</p>
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        <a href="#page-search" class="inline-flex rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300">{{ labels.edit_search }}</a>
                        <Link
                            v-if="hasActiveFilters"
                            :href="searchUrl"
                            :data="paramsFor({ location: null, remote_type: null })"
                            class="inline-flex rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            {{ labels.clear_filters }}
                        </Link>
                    </div>
                </div>

                <!-- Results -->
                <div v-else class="space-y-10">
                    <section v-if="showJobsSection" aria-labelledby="search-jobs-heading">
                        <div class="mb-4 flex items-baseline justify-between gap-3">
                            <h2 id="search-jobs-heading" class="text-xl font-bold text-stone-900 dark:text-white sm:text-2xl">{{ labels.jobs_title }}</h2>
                            <span class="text-sm text-stone-500 dark:text-stone-400">{{ labels.jobs_count.replace(':count', String(jobsCount)) }}</span>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                            <JobCard v-for="job in jobItems" :key="job.id" :job="job" :labels="labels" />
                        </div>
                        <div v-if="jobsHasMore && jobItems.length" class="mt-6 text-center">
                            <p v-if="jobsLoadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                            <button
                                type="button"
                                :disabled="jobsLoadingMore"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                                @click="loadMoreJobs"
                            >
                                {{ jobsLoadingMore ? labels.loading_more : labels.show_more }}
                            </button>
                        </div>
                    </section>

                    <section v-if="showCompaniesSection" aria-labelledby="search-companies-heading">
                        <div class="mb-4 flex items-baseline justify-between gap-3">
                            <h2 id="search-companies-heading" class="text-xl font-bold text-stone-900 dark:text-white sm:text-2xl">{{ labels.companies_title }}</h2>
                            <span class="text-sm text-stone-500 dark:text-stone-400">{{ labels.companies_count.replace(':count', String(companiesCount)) }}</span>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                            <CompanyCard v-for="company in companyItems" :key="company.id" :company="company" :labels="labels" />
                        </div>
                        <div v-if="companiesHasMore && companyItems.length" class="mt-6 text-center">
                            <p v-if="companiesLoadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                            <button
                                type="button"
                                :disabled="companiesLoadingMore"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                                @click="loadMoreCompanies"
                            >
                                {{ companiesLoadingMore ? labels.loading_more : labels.show_more }}
                            </button>
                        </div>
                    </section>
                </div>
            </template>

            <!-- Empty state with popular searches (no criteria) -->
            <div v-else class="rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-12 text-center dark:border-stone-700 dark:bg-stone-900/40">
                <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">{{ labels.start_search }}</h3>
                <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.search_jobs_and_companies }}</p>

                <div v-if="popularSearches.length" class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ labels.try_popular_search }}</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <Link
                            v-for="term in popularSearches"
                            :key="term"
                            data-popular-search
                            :href="searchUrl"
                            :data="{ search: term }"
                            class="inline-flex rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-amber-500/40 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
                        >
                            {{ term }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>