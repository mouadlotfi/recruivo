<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import type { PageProps, Pagination } from '../../types'
import AdminLayout from '../../Components/Admin/AdminLayout.vue'
import AdminBreadcrumb from '../../Components/Admin/AdminBreadcrumb.vue'

type AdminJob = {
    id: number
    title: string
    company: { id: number; name: string } | null
    recruiter: { id: number; name: string; email: string } | null
    status: string
    status_label: string
    published_at: string | null
    published_label: string | null
    closes_at: string | null
    closes_label: string | null
    applications_count: number
    created_at: string | null
    created_label: string | null
}

type JobFilters = {
    search: string
    status: string
    no_applications: boolean
    filter?: string
    job?: number | null
}

type StatusOption = {
    value: string
    label: string
}

const props = defineProps<{
    jobs: AdminJob[]
    pagination: Pagination
    filters: JobFilters
    statusOptions: StatusOption[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`
const items = ref<AdminJob[]>([...props.jobs])
const search = ref(props.filters.search)
const status = ref(props.filters.status)
const noApplications = ref(props.filters.no_applications)
const job = ref<number | null>(props.filters.job ?? null)
const loading = ref(false)

watch(() => props.jobs, (incoming) => {
    const known = new Set(items.value.map((job) => job.id))
    items.value = props.pagination.current_page === 1
        ? [...incoming]
        : [...items.value, ...incoming.filter((job) => !known.has(job.id))]
})

watch(() => props.filters, (filters) => {
    search.value = filters.search
    status.value = filters.status
    noApplications.value = filters.no_applications
    job.value = filters.job ?? null
}, { deep: true })

const hasMore = computed(() => props.pagination.next_page_url !== null)
const hasFilters = computed(() => Boolean(search.value || status.value || noApplications.value || job.value !== null))

const submitFilters = () => {
    router.get(localeUrl('/admin/jobs'), {
        search: search.value || undefined,
        status: status.value || undefined,
        no_applications: noApplications.value ? '1' : undefined,
        job: job.value ?? undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const clearFilters = () => {
    search.value = ''
    status.value = ''
    noApplications.value = false
    job.value = null
    submitFilters()
}

const loadMore = () => {
    if (!props.pagination.next_page_url || loading.value) return

    loading.value = true
    router.get(props.pagination.next_page_url, {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false
        },
    })
}

const statusClass = (jobStatus: string) => jobStatus === 'published'
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
    : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300'
</script>

<template>
    <Head :title="labels.job_management_title" />

    <AdminLayout :labels="labels">
        <div class="space-y-6 sm:space-y-8">
            <header>
                <AdminBreadcrumb :items="[{ label: labels.job_management_title }]" />
                <h1 class="mt-2 text-2xl font-semibold text-stone-900 dark:text-white sm:text-3xl">{{ labels.job_management_title }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.job_management_subtitle }}</p>
            </header>

            <section aria-labelledby="admin-jobs-filters-heading" class="rounded-xl border border-stone-200/60 bg-white/80 p-3 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-4">
                <h2 id="admin-jobs-filters-heading" class="sr-only">{{ labels.search }}</h2>
                <form class="flex flex-col gap-3 xl:flex-row xl:items-end" @submit.prevent="submitFilters">
                    <div class="min-w-0 flex-1">
                        <label for="admin-job-search" class="sr-only">{{ labels.jobs_search_placeholder }}</label>
                        <input
                            id="admin-job-search"
                            v-model="search"
                            type="search"
                            name="search"
                            :placeholder="labels.jobs_search_placeholder"
                            class="min-h-11 w-full rounded-lg border border-stone-200 bg-white px-3 text-sm text-stone-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4"
                        >
                    </div>
                    <div class="xl:w-48">
                        <label for="admin-job-status" class="sr-only">{{ labels.status }}</label>
                        <select
                            id="admin-job-status"
                            v-model="status"
                            name="status"
                            class="min-h-11 w-full rounded-lg border border-stone-200 bg-white px-3 text-sm text-stone-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:px-4"
                        >
                            <option value="">{{ labels.all_statuses }}</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 px-1 text-sm font-medium text-stone-700 transition hover:text-stone-900 dark:text-stone-300 dark:hover:text-white">
                        <input
                            v-model="noApplications"
                            type="checkbox"
                            name="no_applications"
                            class="h-4 w-4 rounded border-stone-300 text-amber-600 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:border-stone-600 dark:bg-stone-800 dark:focus-visible:ring-offset-stone-950"
                        >
                        <span>{{ labels.no_applications_filter }}</span>
                    </label>
                    <div class="flex gap-2 xl:shrink-0">
                        <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:flex-none sm:px-5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                            {{ labels.search_button }}
                        </button>
                        <button v-if="hasFilters" type="button" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-stone-100 px-4 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:flex-none" @click="clearFilters">
                            {{ labels.clear }}
                        </button>
                    </div>
                </form>
                <div v-if="noApplications || job !== null" class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                        v-if="noApplications"
                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        {{ labels.no_applications_filter }}
                        <button
                            type="button"
                            class="inline-flex h-4 w-4 items-center justify-center rounded-full text-amber-600 transition hover:bg-amber-200/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-300 dark:hover:bg-amber-500/20"
                            :aria-label="labels.clear_no_applications"
                            @click="noApplications = false; clearFilters()"
                        >
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                        </button>
                    </span>
                    <span v-if="job !== null" class="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-medium text-stone-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300">
                        {{ labels.viewing_job.replace(':id', String(job)) }}
                    </span>
                </div>
            </section>

            <section aria-labelledby="admin-jobs-table-heading" class="min-w-0 rounded-xl border border-stone-200/60 bg-white/80 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <h2 id="admin-jobs-table-heading" class="sr-only">{{ labels.job_management_title }}</h2>
                <div v-if="items.length === 0" class="p-12 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5h-16.5A1.5 1.5 0 002.25 9v9.75a1.5 1.5 0 001.5 1.5h16.5a1.5 1.5 0 001.5-1.5V9a1.5 1.5 0 001.5-1.5zM8.25 7.5V6A2.25 2.25 0 0110.5 3.75h3A2.25 2.25 0 0115.75 6v1.5M2.25 12h19.5" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_jobs_found }}</h3>
                    <p v-if="hasFilters" class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ labels.no_jobs_match }}</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-[68rem] w-full text-left text-sm">
                        <caption class="sr-only">{{ labels.job_management_title }}</caption>
                        <thead class="border-b border-stone-200/80 text-xs uppercase tracking-[0.1em] text-stone-500 dark:border-stone-800 dark:text-stone-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.title }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.company }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.recruiter }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.status }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.published }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.closing_date }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">{{ labels.applications }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ labels.created }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200/70 dark:divide-stone-800">
                            <tr v-for="job in items" :key="job.id" class="align-top text-stone-700 dark:text-stone-300">
                                <td class="max-w-[16rem] px-4 py-4">
                                    <p class="font-semibold text-stone-900 dark:text-white">{{ job.title }}</p>
                                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">#{{ job.id }}</p>
                                </td>
                                <td class="px-4 py-4">{{ job.company?.name ?? '—' }}</td>
                                <td class="max-w-[14rem] px-4 py-4">
                                    <p>{{ job.recruiter?.name ?? '—' }}</p>
                                    <p v-if="job.recruiter?.email" class="mt-1 truncate text-xs text-stone-500 dark:text-stone-400">{{ job.recruiter.email }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span :class="['inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold', statusClass(job.status)]">{{ job.status_label }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-stone-600 dark:text-stone-400">{{ job.published_label ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-stone-600 dark:text-stone-400">{{ job.closes_label ?? '—' }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-stone-900 dark:text-white">{{ job.applications_count }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-stone-600 dark:text-stone-400">{{ job.created_label ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div v-if="hasMore" class="flex min-h-12 items-center justify-center">
                <button
                    type="button"
                    :disabled="loading"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:cursor-wait disabled:opacity-70 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                    @click="loadMore"
                >
                    {{ loading ? labels.loading : labels.show_more }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
