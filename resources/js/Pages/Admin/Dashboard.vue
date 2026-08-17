<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import AdminLayout from '../../Components/Admin/AdminLayout.vue'
import DashboardChart from '../../Components/Admin/DashboardChart.vue'

type MetricKey = 'users' | 'jobs' | 'applications' | 'recruiters'
type Comparison = { percentage: number; direction: 'up' | 'down' | 'flat' } | null

type Metric = {
    value: number
    period_count: number
    comparison: Comparison
}

type DashboardData = {
    range: number
    period: { start: string; end: string; label: string }
    metrics: Record<MetricKey | 'live_jobs', Metric>
    attention: { key: string; count: number; action_url: string | null }[]
    growth: {
        labels: string[]
        series: Record<MetricKey, number[]>
    }
    activity: {
        labels: string[]
        series: Record<'applications' | 'jobs' | 'users', number[]>
    }
    marketplace: {
        average_applications_per_live_job: number | null
        jobs_without_applications: number
        candidate_activation: number | null
        recruiter_activation: number | null
    }
    recentActivity: {
        kind: string
        event_label: string
        actor: string
        detail: string
        occurred_at: string | null
        time_label: string
        url: string | null
    }[]
    systemHealth: {
        key: string
        status: string
        status_label: string
        label: string
        value: number | null
    }[]
}

const props = defineProps<{
    dashboard: DashboardData
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`
const selectedRange = ref(String(props.dashboard.range))
const activeMetric = ref<MetricKey>('applications')
const isRefreshing = ref(false)

watch(() => props.dashboard.range, (range) => {
    selectedRange.value = String(range)
})

const rangeOptions = computed(() => [
    { value: '7', label: props.labels.last_7_days },
    { value: '30', label: props.labels.last_30_days },
    { value: '90', label: props.labels.last_90_days },
    { value: '365', label: props.labels.last_year },
])

const metricCards = computed(() => [
    { key: 'users' as const, label: props.labels.users, periodLabel: props.labels.users_period },
    { key: 'live_jobs' as const, label: props.labels.live_jobs, periodLabel: props.labels.jobs_period },
    { key: 'applications' as const, label: props.labels.applications, periodLabel: props.labels.applications_period },
    { key: 'recruiters' as const, label: props.labels.recruiters, periodLabel: props.labels.recruiters_period },
])

const metricOptions = computed(() => [
    { key: 'users' as const, label: props.labels.metric_users },
    { key: 'jobs' as const, label: props.labels.metric_jobs },
    { key: 'applications' as const, label: props.labels.metric_applications },
    { key: 'recruiters' as const, label: props.labels.metric_recruiters },
])

const growthDataset = computed(() => [{
    label: props.labels[`metric_${activeMetric.value}`],
    data: props.dashboard.growth.series[activeMetric.value],
    color: '#d97706',
}])
const growthAriaLabel = computed(() => `${props.labels.platform_growth}: ${props.labels[`metric_${activeMetric.value}`]}`)

const activityDatasets = computed(() => [
    { label: props.labels.activity_applications, data: props.dashboard.activity.series.applications, color: '#d97706' },
    { label: props.labels.activity_jobs, data: props.dashboard.activity.series.jobs, color: '#0f766e' },
    { label: props.labels.activity_users, data: props.dashboard.activity.series.users, color: '#78716c' },
])

const marketplaceRows = computed(() => [
    {
        key: 'average_applications_per_live_job',
        label: props.labels.average_applications_per_live_job,
        value: props.dashboard.marketplace.average_applications_per_live_job,
        format: 'decimal',
    },
    {
        key: 'jobs_without_applications',
        label: props.labels.jobs_without_applications.replace(':count', ''),
        value: props.dashboard.marketplace.jobs_without_applications,
        format: 'integer',
    },
    {
        key: 'candidate_activation',
        label: props.labels.candidate_activation,
        value: props.dashboard.marketplace.candidate_activation,
        format: 'percent',
    },
    {
        key: 'recruiter_activation',
        label: props.labels.recruiter_activation,
        value: props.dashboard.marketplace.recruiter_activation,
        format: 'percent',
    },
])

const formatNumber = (value: number) => new Intl.NumberFormat(locale.value).format(value)
const formatDecimal = (value: number) => new Intl.NumberFormat(locale.value, { maximumFractionDigits: 1 }).format(value)
const formatMarketplaceValue = (value: number | null, format: string) => {
    if (value === null) return props.labels.no_data
    if (format === 'percent') return `${formatDecimal(value)}%`
    if (format === 'decimal') return formatDecimal(value)

    return formatNumber(value)
}

const comparisonText = (comparison: Comparison) => {
    if (!comparison) return props.labels.no_comparison
    if (comparison.direction === 'flat') return `0% ${props.labels.compared_to_previous}`

    const arrow = comparison.direction === 'up' ? '↑' : '↓'
    const percentage = formatDecimal(Math.abs(comparison.percentage))

    return `${arrow} ${percentage}% ${props.labels.compared_to_previous}`
}

const comparisonClass = (comparison: Comparison) => {
    if (!comparison || comparison.direction === 'flat') return 'text-stone-500 dark:text-stone-400'

    return comparison.direction === 'up'
        ? 'text-emerald-700 dark:text-emerald-400'
        : 'text-red-700 dark:text-red-400'
}

const attentionTitle = (item: DashboardData['attention'][number]) =>
    props.labels[item.key].replace(':count', formatNumber(item.count))

const healthClass = (status: string) => {
    if (status === 'error' || status === 'unavailable') return 'text-red-700 dark:text-red-400'
    if (status === 'healthy' || status === 'clear') return 'text-emerald-700 dark:text-emerald-400'

    return 'text-amber-700 dark:text-amber-400'
}

const changeRange = () => {
    isRefreshing.value = true
    router.get(localeUrl('/admin/dashboard'), { range: selectedRange.value }, {
        only: ['dashboard'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        onFinish: () => {
            isRefreshing.value = false
        },
    })
}
</script>

<template>
    <Head :title="labels.title" />

    <AdminLayout :labels="labels">
        <div class="space-y-6 sm:space-y-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">{{ labels.admin_area }}</p>
                    <h1 class="break-words text-2xl font-semibold text-stone-900 dark:text-white sm:text-3xl">{{ labels.title }}</h1>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-400">{{ labels.subtitle }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <label for="admin-date-range" class="sr-only">{{ labels.range }}</label>
                    <select
                        id="admin-date-range"
                        v-model="selectedRange"
                        :disabled="isRefreshing"
                        :aria-busy="isRefreshing"
                        class="min-h-11 w-full rounded-lg border border-stone-200 bg-white px-3 text-sm font-medium text-stone-700 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 disabled:cursor-wait disabled:opacity-70 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:focus:border-amber-400 dark:focus:ring-amber-500/20 sm:w-auto"
                        @change="changeRange"
                    >
                        <option v-for="option in rangeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <span v-if="isRefreshing" class="sr-only" role="status">{{ labels.loading }}</span>
                </div>
            </header>

            <section aria-labelledby="admin-kpi-heading">
                <h2 id="admin-kpi-heading" class="sr-only">{{ labels.title }}</h2>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in metricCards" :key="card.key" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.13em] text-stone-500 dark:text-stone-400">{{ card.label }}</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 dark:text-white">{{ formatNumber(dashboard.metrics[card.key].value) }}</p>
                        <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                            +{{ formatNumber(dashboard.metrics[card.key].period_count) }} {{ card.periodLabel }}
                        </p>
                        <p :class="['mt-1 text-xs font-medium', comparisonClass(dashboard.metrics[card.key].comparison)]">
                            {{ comparisonText(dashboard.metrics[card.key].comparison) }}
                        </p>
                    </article>
                </div>
            </section>

            <section aria-labelledby="attention-heading" class="rounded-xl border border-stone-200/70 bg-white/80 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70">
                <div class="flex items-center justify-between border-b border-stone-200/70 px-4 py-4 dark:border-stone-800 sm:px-5">
                    <h2 id="attention-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.needs_attention }}</h2>
                </div>
                <div v-if="dashboard.attention.length === 0" class="flex items-center gap-3 px-4 py-5 text-sm text-stone-600 dark:text-stone-400 sm:px-5">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400" aria-hidden="true">✓</span>
                    <span>{{ labels.nothing_needs_attention }}</span>
                </div>
                <ul v-else class="divide-y divide-stone-200/70 dark:divide-stone-800">
                    <li v-for="item in dashboard.attention" :key="item.key" class="flex flex-col gap-3 border-l-2 border-amber-400 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300" aria-hidden="true">!</span>
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900 dark:text-white">{{ attentionTitle(item) }}</p>
                                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ labels[`${item.key}_description`] }}</p>
                            </div>
                        </div>
                        <Link v-if="item.action_url" :href="item.action_url" class="inline-flex min-h-11 shrink-0 items-center text-sm font-semibold text-amber-700 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-400 dark:hover:text-amber-300">
                            {{ labels.view_jobs }}
                            <span class="ml-1" aria-hidden="true">→</span>
                        </Link>
                    </li>
                </ul>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(17rem,1fr)]">
                <section aria-labelledby="growth-heading" class="min-w-0 rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 id="growth-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.platform_growth }}</h2>
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ labels.growth_help }}</p>
                        </div>
                        <div class="flex max-w-full overflow-x-auto rounded-lg border border-stone-200/80 bg-stone-50/80 p-1 dark:border-stone-700 dark:bg-stone-950/50" role="tablist" :aria-label="labels.platform_growth">
                            <button
                                v-for="option in metricOptions"
                                :key="option.key"
                                type="button"
                                role="tab"
                                :aria-selected="activeMetric === option.key"
                                :class="activeMetric === option.key ? 'bg-white text-amber-700 shadow-sm dark:bg-stone-800 dark:text-amber-300' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100'"
                                class="min-h-9 whitespace-nowrap rounded-md px-2.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 sm:px-3"
                                @click="activeMetric = option.key"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>
                    <div class="mt-5">
                        <DashboardChart
                            type="line"
                            :labels="dashboard.growth.labels"
                            :datasets="growthDataset"
                            :aria-label="growthAriaLabel"
                            :empty-text="labels.no_chart_data"
                        />
                    </div>
                </section>

                <section aria-labelledby="health-heading" class="min-w-0 rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                    <div>
                        <h2 id="health-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.marketplace_health }}</h2>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ labels.growth_help }}</p>
                    </div>
                    <dl class="mt-5 divide-y divide-stone-200/70 dark:divide-stone-800">
                        <div v-for="row in marketplaceRows" :key="row.key" class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <dt class="max-w-[13rem] text-sm text-stone-600 dark:text-stone-400">{{ row.label }}</dt>
                            <dd class="shrink-0 text-right text-sm font-semibold text-stone-900 dark:text-white">{{ formatMarketplaceValue(row.value, row.format) }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section aria-labelledby="activity-chart-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div>
                    <h2 id="activity-chart-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.platform_activity }}</h2>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ labels.activity_help }}</p>
                </div>
                <div class="mt-5">
                    <DashboardChart
                        type="bar"
                        :labels="dashboard.activity.labels"
                        :datasets="activityDatasets"
                        :aria-label="labels.platform_activity"
                        :empty-text="labels.no_chart_data"
                    />
                </div>
            </section>

            <section aria-labelledby="recent-activity-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="recent-activity-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.recent_activity }}</h2>
                </div>
                <div v-if="dashboard.recentActivity.length === 0" class="py-10 text-center text-sm text-stone-500 dark:text-stone-400">
                    {{ labels.no_recent_activity }}
                </div>
                <div v-else class="mt-4 overflow-x-auto">
                    <table class="min-w-[38rem] w-full text-left text-sm">
                        <caption class="sr-only">{{ labels.recent_activity }}</caption>
                        <thead class="border-b border-stone-200/80 text-xs uppercase tracking-[0.1em] text-stone-500 dark:border-stone-800 dark:text-stone-400">
                            <tr>
                                <th scope="col" class="px-3 py-3 font-semibold">{{ labels.event }}</th>
                                <th scope="col" class="px-3 py-3 font-semibold">{{ labels.user }}</th>
                                <th scope="col" class="px-3 py-3 font-semibold">{{ labels.details }}</th>
                                <th scope="col" class="px-3 py-3 text-right font-semibold">{{ labels.time }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200/70 dark:divide-stone-800">
                            <tr v-for="activity in dashboard.recentActivity" :key="`${activity.kind}-${activity.occurred_at}-${activity.actor}`" class="text-stone-700 dark:text-stone-300">
                                <td class="px-3 py-3 font-medium text-stone-900 dark:text-white">{{ activity.event_label }}</td>
                                <td class="px-3 py-3">{{ activity.actor }}</td>
                                <td class="max-w-[18rem] px-3 py-3">
                                    <Link v-if="activity.url" :href="activity.url" class="font-medium text-amber-700 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-400 dark:hover:text-amber-300">
                                        {{ activity.detail }}
                                    </Link>
                                    <span v-else>{{ activity.detail }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-right text-xs text-stone-500 dark:text-stone-400">
                                    <time :datetime="activity.occurred_at ?? undefined">{{ activity.time_label }}</time>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="system-health" aria-labelledby="system-health-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                    <h2 id="system-health-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.system_health }}</h2>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ dashboard.period.label }}</p>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="health in dashboard.systemHealth" :key="health.key" class="flex min-w-0 items-center justify-between gap-3 rounded-lg border border-stone-200/70 px-3 py-3 dark:border-stone-800">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-stone-800 dark:text-stone-200">{{ health.label }}</p>
                            <p v-if="health.key === 'failed_jobs' && health.value !== null" class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">{{ formatNumber(health.value) }}</p>
                        </div>
                        <span :class="['shrink-0 text-xs font-semibold', healthClass(health.status)]">{{ health.status_label }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
