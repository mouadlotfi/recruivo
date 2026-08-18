<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

type DashboardChartDataset = {
    label: string
    data: number[]
    color: string
    colors?: string[]
}

const props = defineProps<{
    type: 'line' | 'bar'
    labels: string[]
    datasets: DashboardChartDataset[]
    ariaLabel: string
    emptyText: string
    locale: string
    indexAxis?: 'x' | 'y'
}>()

const canvas = ref<HTMLCanvasElement | null>(null)
const hasData = computed(() => props.datasets.some((dataset) => dataset.data.some((value) => value > 0)))
const formatValue = (value: number) => new Intl.NumberFormat(props.locale).format(value)
const summary = computed(() => props.datasets
    .map((dataset) => `${dataset.label}: ${formatValue(dataset.data.reduce((total, value) => total + value, 0))}`)
    .join('; '))
const accessibleSummary = computed(() => hasData.value ? summary.value : props.emptyText)

let chart: Chart | null = null
let themeObserver: MutationObserver | null = null

const isDarkMode = () => document.documentElement.classList.contains('dark')
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
const resolveColor = (color: string) => {
    if (!color.startsWith('var(')) return color

    const tokenName = color.slice(4, -1).trim()
    return getComputedStyle(document.documentElement).getPropertyValue(tokenName).trim() || color
}

const destroyChart = () => {
    chart?.destroy()
    chart = null
}

const renderChart = () => {
    destroyChart()

    if (!canvas.value || !hasData.value) {
        return
    }

    const dark = isDarkMode()
    const axisColor = dark ? '#a8a29e' : '#78716c'
    const gridColor = dark ? 'rgba(168, 162, 158, 0.16)' : 'rgba(120, 113, 108, 0.16)'
    const indexAxis = props.indexAxis ?? 'x'
    const isHorizontal = props.type === 'bar' && indexAxis === 'y'

    chart = new Chart(canvas.value, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: props.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: resolveColor(dataset.color),
                backgroundColor: props.type === 'bar'
                    ? (dataset.colors?.map(resolveColor) ?? `${resolveColor(dataset.color)}b8`)
                    : 'transparent',
                borderWidth: props.type === 'line' ? 2 : 1,
                borderRadius: props.type === 'bar' ? 3 : 0,
                pointRadius: props.type === 'line' ? 2 : 0,
                pointHoverRadius: props.type === 'line' ? 4 : 0,
                pointBackgroundColor: resolveColor(dataset.color),
                pointBorderColor: dark ? '#1c1917' : '#ffffff',
                pointBorderWidth: props.type === 'line' ? 1.5 : 0,
                tension: props.type === 'line' ? 0.25 : 0,
                fill: false,
                maxBarThickness: isHorizontal ? 28 : 24,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis,
            animation: {
                duration: prefersReducedMotion() ? 0 : 450,
            },
            interaction: {
                mode: isHorizontal ? 'nearest' : 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: props.datasets.length > 1,
                    labels: {
                        color: axisColor,
                        usePointStyle: true,
                        boxWidth: 7,
                        boxHeight: 7,
                        padding: 18,
                        font: {
                            family: 'Plus Jakarta Sans, sans-serif',
                            size: 11,
                        },
                    },
                },
                tooltip: {
                    displayColors: false,
                    callbacks: {
                        title: (items) => items[0]?.label ?? '',
                        label: (item) => `${item.dataset.label}: ${formatValue(Number(item.raw))}`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: isHorizontal,
                    grid: {
                        color: isHorizontal ? gridColor : 'transparent',
                        display: isHorizontal,
                    },
                    ticks: {
                        color: axisColor,
                        maxTicksLimit: isHorizontal ? 6 : (props.type === 'bar' ? 8 : 7),
                        autoSkip: true,
                        precision: 0,
                        font: {
                            family: 'Plus Jakarta Sans, sans-serif',
                            size: 10,
                        },
                    },
                    border: {
                        color: isHorizontal ? 'transparent' : gridColor,
                    },
                },
                y: {
                    beginAtZero: !isHorizontal,
                    grid: {
                        color: isHorizontal ? 'transparent' : gridColor,
                        display: !isHorizontal,
                    },
                    ticks: {
                        color: axisColor,
                        precision: 0,
                        font: {
                            family: 'Plus Jakarta Sans, sans-serif',
                            size: 10,
                        },
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    })
}

watch(() => [props.type, props.indexAxis, props.locale, props.labels, props.datasets, hasData.value], renderChart, { deep: true, flush: 'post' })

onMounted(() => {
    renderChart()
    themeObserver = new MutationObserver(renderChart)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
    themeObserver?.disconnect()
    destroyChart()
})
</script>

<template>
    <div
        role="img"
        :aria-label="hasData ? ariaLabel : emptyText"
        class="min-w-0"
    >
        <p class="sr-only">{{ accessibleSummary }}</p>
        <div v-if="hasData" class="relative h-64 w-full sm:h-72">
            <canvas ref="canvas" aria-hidden="true"></canvas>
        </div>
        <div v-else class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-stone-200 px-6 text-center text-sm text-stone-500 dark:border-stone-700 dark:text-stone-400 sm:min-h-72">
            {{ emptyText }}
        </div>
    </div>
</template>
