<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import AdminLayout from '../../Components/Admin/AdminLayout.vue'
import DashboardChart from '../../Components/Admin/DashboardChart.vue'

type MetricKey = 'users' | 'jobs' | 'applications' | 'recruiters'
type MetricCardKey = 'users' | 'live_jobs' | 'applications' | 'recruiters'
type Comparison = { percentage: number; direction: 'up' | 'down' | 'flat' } | null

type SnapshotMetric = {
    value: number
    period_count: number
    comparison: Comparison
}

type ApplicationsMetric = {
    value: number
    comparison: Comparison
}

type AttentionItem = {
    key: string
    count: number
    title: string
    description: string
    action_label: string
    action_url: string | null
    action: { label: string; url: string | null }
    status: string
    status_label: string
}

type PipelineStatus = {
    key: string
    label: string
    count: number
}

type DashboardData = {
    range: number
    period: { start: string; end: string; label: string }
    metrics: {
        users: SnapshotMetric
        live_jobs: SnapshotMetric
        applications: ApplicationsMetric
        recruiters: SnapshotMetric
    }
    attention: AttentionItem[]
    pipeline: {
        range: number
        period: { start: string; end: string }
        status_keys: string[]
        counts: Record<string, number>
        statuses: PipelineStatus[]
    }
    growth: {
        aggregation: string
        labels: string[]
        series: Record<MetricKey, number[]>
    }
    activity: {
        labels: string[]
        series: Record<string, number[]>
    }
    marketplace: {
        average_applications_per_live_job: number | null
        jobs_without_applications: number
        candidate_activation: number | null
        recruiters_with_live_jobs: number | null
    }
    recentActivity: {
        id: string
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

watchRange()
function watchRange() {
    // keep local select in sync if range prop changes
    selectedRange.value = String(props.dashboard.range)
}

const rangeOptions = computed(() => [
    { value: '7', label: props.labels.last_7_days },
    { value: '30', label: props.labels.last_30_days },
    { value: '90', label: props.labels.last_90_days },
    { value: '365', label: props.labels.last_year },
])

const metricCards = computed(() => [
    { key: 'users' as const, label: props.labels.users, periodLabel: props.labels.users_period, href: localeUrl('/admin/users'), accent: 'amber' },
    { key: 'live_jobs' as const, label: props.labels.live_jobs, periodLabel: props.labels.jobs_period, href: localeUrl('/admin/jobs?status=published'), accent: 'teal' },
    { key: 'applications' as const, label: props.labels.applications, periodLabel: props.labels.applications_period, href: null, accent: 'stone' },
    { key: 'recruiters' as const, label: props.labels.recruiters, periodLabel: props.labels.recruiters_period, href: localeUrl('/admin/users?role=Recruiter'), accent: 'amber' },
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
    color: 'var(--recruivo-chart-amber)',
}])
const growthAriaLabel = computed(() => `${props.labels.platform_growth}: ${props.labels[`metric_${activeMetric.value}`]}`)

const statusColors: Record<string, string> = {
    pending: 'var(--recruivo-chart-pending)',
    shortlisted: 'var(--recruivo-chart-shortlisted)',
    interview: 'var(--recruivo-chart-interview)',
    accepted: 'var(--recruivo-chart-accepted)',
    rejected: 'var(--recruivo-chart-rejected)',
    withdrawn: 'var(--recruivo-chart-withdrawn)',
}

const pipelineTotal = computed(() => props.dashboard.pipeline.statuses.reduce((sum, s) => sum + s.count, 0))
const applicationPipelineTitle = computed(() => props.labels.application_pipeline)
const applicationPipelineHelp = computed(() => props.labels.pipeline_help)
const pipelineDataset = computed(() => [{
    label: props.labels.applications,
    data: props.dashboard.pipeline.statuses.map((status) => status.count),
    color: 'var(--recruivo-chart-teal)',
    colors: props.dashboard.pipeline.statuses.map((status) => statusColors[status.key] ?? 'var(--recruivo-chart-stone)'),
}])
const pipelineAriaLabel = computed(() => `${applicationPipelineTitle.value}: ${props.dashboard.pipeline.statuses.map((status) => `${status.label} ${formatNumber(status.count)}`).join(', ')}`)

const marketplaceHelp = computed(() => props.labels.marketplace_help)
const candidateActivationHelp = computed(() => props.labels.candidate_activation_help)

const marketplaceRows = computed(() => [
    { key: 'average_applications_per_live_job', label: props.labels.average_applications_per_live_job, value: props.dashboard.marketplace.average_applications_per_live_job, format: 'decimal', help: '' },
    { key: 'jobs_without_applications', label: props.labels.jobs_without_applications_metric, value: props.dashboard.marketplace.jobs_without_applications, format: 'integer', help: '' },
    { key: 'candidate_activation', label: props.labels.candidate_activation, value: props.dashboard.marketplace.candidate_activation, format: 'percent', help: candidateActivationHelp.value },
    { key: 'recruiters_with_live_jobs', label: props.labels.recruiters_with_live_jobs, value: props.dashboard.marketplace.recruiters_with_live_jobs, format: 'percent', help: '' },
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

const attentionTitle = (item: AttentionItem) => item.title || props.labels[item.key] || item.key
const attentionDescription = (item: AttentionItem) => item.description || props.labels[`${item.key}_description`] || ''
const attentionClass = (status: string) => status === 'requires_review'
    ? 'border-red-400'
    : 'border-amber-400'
const attentionActionClass = (status: string) => status === 'requires_review'
    ? 'text-red-700 dark:text-red-400'
    : 'text-amber-700 dark:text-amber-400'

const healthClass = (status: string) => {
    if (status === 'error' || status === 'unavailable') return 'text-red-700 dark:text-red-400'
    if (status === 'healthy' || status === 'clear') return 'text-emerald-700 dark:text-emerald-400'
    return 'text-amber-700 dark:text-amber-400'
}
const healthDotClass = (status: string) => {
    if (status === 'error' || status === 'unavailable') return 'bg-red-500'
    if (status === 'healthy' || status === 'clear') return 'bg-emerald-500'
    return 'bg-amber-500'
}

const accentBorder = (accent: string) => {
    if (accent === 'amber') return 'before:bg-amber-500'
    if (accent === 'teal') return 'before:bg-teal-600'
    return 'before:bg-stone-400'
}

const changeRange = () => {
    isRefreshing.value = true
    router.get(localeUrl('/admin/dashboard/preview'), { range: selectedRange.value }, {
        only: ['dashboard'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        showProgress: false,
        onFinish: () => { isRefreshing.value = false },
    })
}
</script>

<template>
    <Head :title="`${labels.title} — ${labels.admin_area ?? ''}`" />

    <AdminLayout :labels="labels">
        <div class="space-y-6 sm:space-y-8">
            <!-- Header -->
            <header class="flex flex-col gap-4 border-b border-stone-200/70 pb-5 dark:border-stone-800 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-400">{{ labels.admin_area }}</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold text-stone-900 dark:text-white sm:text-3xl">{{ labels.title }}</h1>
                    <p class="mt-1.5 text-sm text-stone-600 dark:text-stone-400">{{ dashboard.period.label }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white/70 px-2.5 py-1 text-[11px] font-medium text-stone-500 dark:border-stone-700 dark:bg-stone-900/60 dark:text-stone-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        {{ labels.admin_area }} preview
                    </span>
                    <label for="preview-date-range" class="sr-only">{{ labels.range }}</label>
                    <select
                        id="preview-date-range"
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

            <!-- KPI summary -->
            <section aria-labelledby="preview-kpi-heading">
                <h2 id="preview-kpi-heading" class="sr-only">{{ labels.title }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    <Link
                        v-for="card in metricCards"
                        :key="card.key"
                        :href="card.href ?? '#'"
                        :aria-disabled="card.href ? undefined : 'true'"
                        :tabindex="card.href ? undefined : '-1'"
                        :class="['group relative overflow-hidden rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur transition before:absolute before:left-0 before:top-0 before:h-full before:w-1 before:content-[\'\'] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-800 dark:bg-stone-900/70 sm:p-5', accentBorder(card.accent), card.href ? 'hover:border-amber-300 hover:shadow-sm dark:hover:border-amber-500/40' : 'pointer-events-none']"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.13em] text-stone-500 dark:text-stone-400">{{ card.label }}</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 dark:text-white">{{ formatNumber(dashboard.metrics[card.key].value) }}</p>
                        <div class="mt-3 flex items-center gap-2 text-xs">
                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 font-semibold', comparisonClass(dashboard.metrics[card.key].comparison)]">
                                {{ comparisonText(dashboard.metrics[card.key].comparison) }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-xs text-stone-500 dark:text-stone-400">
                            <template v-if="card.key === 'applications'">{{ card.periodLabel }}</template>
                            <template v-else>+{{ formatNumber(dashboard.metrics[card.key].period_count) }} {{ card.periodLabel }}</template>
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Growth (2/3) + Attention (1/3) -->
            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <section aria-labelledby="preview-growth-heading" class="min-w-0 rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                    <div class="flex items-center gap-3 border-b border-stone-200/70 pb-3 dark:border-stone-800">
                        <span class="h-4 w-1 rounded-full bg-amber-500" aria-hidden="true"></span>
                        <div>
                            <h2 id="preview-growth-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.platform_growth }}</h2>
                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.growth_help }}</p>
                        </div>
                        <div class="ml-auto flex max-w-full overflow-x-auto rounded-lg border border-stone-200/80 bg-stone-50/80 p-1 dark:border-stone-700 dark:bg-stone-950/50" role="group" :aria-label="labels.platform_growth">
                            <button
                                v-for="option in metricOptions"
                                :key="option.key"
                                type="button"
                                :aria-pressed="activeMetric === option.key"
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
                            :locale="locale"
                            :chart-label="growthAriaLabel"
                            :empty-text="labels.no_chart_data"
                        />
                    </div>
                </section>

                <section aria-labelledby="preview-attention-heading" class="rounded-xl border border-stone-200/70 bg-white/80 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70">
                    <div class="flex items-center gap-3 border-b border-stone-200/70 px-4 py-4 dark:border-stone-800 sm:px-5">
                        <span class="h-4 w-1 rounded-full bg-amber-500" aria-hidden="true"></span>
                        <h2 id="preview-attention-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.needs_attention }}</h2>
                    </div>
                    <div v-if="dashboard.attention.length === 0" class="flex items-center gap-3 px-4 py-6 text-sm text-stone-600 dark:text-stone-400 sm:px-5">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400" aria-hidden="true">✓</span>
                        <span>{{ labels.nothing_needs_attention }}</span>
                    </div>
                    <ul v-else class="divide-y divide-stone-200/70 dark:divide-stone-800">
                        <li v-for="item in dashboard.attention" :key="item.key" :class="['flex flex-col gap-3 border-l-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5', attentionClass(item.status)]">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300" aria-hidden="true">!</span>
                                <div class="min-w-0">
                                    <p class="font-medium text-stone-900 dark:text-white">{{ attentionTitle(item) }}</p>
                                    <p v-if="attentionDescription(item)" class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ attentionDescription(item) }}</p>
                                </div>
                            </div>
                            <Link v-if="item.action.url" :href="item.action.url" class="inline-flex min-h-11 shrink-0 items-center text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500" :class="attentionActionClass(item.status)">
                                {{ item.action.label }} <span class="ml-1" aria-hidden="true">→</span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Application pipeline (segmented bar) -->
            <section aria-labelledby="preview-pipeline-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex items-center gap-3 border-b border-stone-200/70 pb-3 dark:border-stone-800">
                    <span class="h-4 w-1 rounded-full bg-teal-600" aria-hidden="true"></span>
                    <div>
                        <h2 id="preview-pipeline-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ applicationPipelineTitle }}</h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ applicationPipelineHelp }}</p>
                    </div>
                </div>
                <div class="mt-5">
                    <div
                        v-if="pipelineTotal > 0"
                        class="flex h-3 w-full overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800"
                        role="img"
                        :aria-label="pipelineAriaLabel"
                    >
                        <div
                            v-for="status in dashboard.pipeline.statuses"
                            :key="status.key"
                            class="h-full"
                            :style="{ width: `${(status.count / pipelineTotal) * 100}%`, backgroundColor: `var(--recruivo-chart-${status.key === 'pending' ? 'pending' : (status.key === 'shortlisted' ? 'shortlisted' : (status.key === 'interview' ? 'interview' : (status.key === 'accepted' ? 'accepted' : (status.key === 'rejected' ? 'rejected' : 'withdrawn'))))})` }"
                            :title="`${status.label}: ${formatNumber(status.count)}`"
                        ></div>
                    </div>
                    <div v-else class="text-sm text-stone-500 dark:text-stone-400">{{ labels.no_chart_data }}</div>
                    <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3 lg:grid-cols-6">
                        <li v-for="status in dashboard.pipeline.statuses" :key="status.key" class="flex items-center gap-2 text-xs">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: statusColors[status.key] ?? 'var(--recruivo-chart-stone)' }" aria-hidden="true"></span>
                            <span class="text-stone-600 dark:text-stone-400">{{ status.label }}</span>
                            <span class="ml-auto font-semibold text-stone-900 dark:text-white">{{ formatNumber(status.count) }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Marketplace health -->
            <section aria-labelledby="preview-marketplace-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex items-center gap-3 border-b border-stone-200/70 pb-3 dark:border-stone-800">
                    <span class="h-4 w-1 rounded-full bg-teal-600" aria-hidden="true"></span>
                    <div>
                        <h2 id="preview-marketplace-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.marketplace_health }}</h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ marketplaceHelp }}</p>
                    </div>
                </div>
                <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div v-for="row in marketplaceRows" :key="row.key" class="flex items-start justify-between gap-4 border-b border-stone-200/60 pb-3 dark:border-stone-800">
                        <div class="min-w-0">
                            <dt class="text-sm text-stone-600 dark:text-stone-400">{{ row.label }}</dt>
                            <p v-if="row.help" class="mt-1 max-w-[16rem] text-xs leading-5 text-stone-500 dark:text-stone-500">{{ row.help }}</p>
                        </div>
                        <dd class="shrink-0 text-right text-lg font-semibold text-stone-900 dark:text-white">{{ formatMarketplaceValue(row.value, row.format) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Recent activity (timeline) -->
            <section aria-labelledby="preview-activity-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex items-center gap-3 border-b border-stone-200/70 pb-3 dark:border-stone-800">
                    <span class="h-4 w-1 rounded-full bg-amber-500" aria-hidden="true"></span>
                    <div>
                        <h2 id="preview-activity-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.recent_activity }}</h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.activity_help }}</p>
                    </div>
                </div>
                <div v-if="dashboard.recentActivity.length === 0" class="py-10 text-center text-sm text-stone-500 dark:text-stone-400">
                    {{ labels.no_recent_activity }}
                </div>
                <ol v-else class="mt-5 space-y-0">
                    <li v-for="(activity, index) in dashboard.recentActivity" :key="activity.id" class="relative flex gap-4 pb-5 last:pb-0">
                        <div class="relative flex flex-col items-center">
                            <span class="z-10 mt-1 inline-flex h-3 w-3 shrink-0 rounded-full border-2 border-white bg-amber-500 dark:border-stone-900" aria-hidden="true"></span>
                            <span v-if="index !== dashboard.recentActivity.length - 1" class="absolute top-4 h-full w-px bg-stone-200 dark:bg-stone-700" aria-hidden="true"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                <p class="text-sm font-semibold text-stone-900 dark:text-white">{{ activity.event_label }}</p>
                                <time class="shrink-0 text-xs text-stone-500 dark:text-stone-400" :datetime="activity.occurred_at ?? undefined">{{ activity.time_label }}</time>
                            </div>
                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">{{ activity.actor }}</p>
                            <Link v-if="activity.url" :href="activity.url" class="mt-1 inline-block truncate text-sm font-medium text-amber-700 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-400 dark:hover:text-amber-300">
                                {{ activity.detail }}
                            </Link>
                            <p v-else class="mt-1 truncate text-sm text-stone-600 dark:text-stone-300">{{ activity.detail }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- System health -->
            <section id="preview-system-health" aria-labelledby="preview-system-health-heading" class="rounded-xl border border-stone-200/70 bg-white/80 p-4 backdrop-blur dark:border-stone-800 dark:bg-stone-900/70 sm:p-5">
                <div class="flex items-center gap-3 border-b border-stone-200/70 pb-3 dark:border-stone-800">
                    <span class="h-4 w-1 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    <h2 id="preview-system-health-heading" class="text-base font-semibold text-stone-900 dark:text-white">{{ labels.system_health }}</h2>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="health in dashboard.systemHealth" :key="health.key" class="flex min-w-0 items-center justify-between gap-3 rounded-lg border border-stone-200/70 px-3 py-3 dark:border-stone-800">
                        <div class="flex min-w-0 items-center gap-2">
                            <span :class="['h-2.5 w-2.5 shrink-0 rounded-full', healthDotClass(health.status)]" aria-hidden="true"></span>
                            <p class="truncate text-sm font-medium text-stone-800 dark:text-stone-200">{{ health.label }}</p>
                        </div>
                        <span :class="['shrink-0 text-xs font-semibold', healthClass(health.status)]">{{ health.status_label }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

