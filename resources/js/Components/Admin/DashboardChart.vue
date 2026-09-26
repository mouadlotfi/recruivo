<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, useId, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

type DashboardChartDataset = {
    label: string
    data: number[]
    color: string
}

const props = defineProps<{
    type: 'line'
    labels: string[]
    datasets: DashboardChartDataset[]
    chartLabel: string
    emptyText: string
    locale: string
}>()

// The canvas is addressed by its id (Chart.js accepts a string selector) so
// the SFC never has to hand a DOM element across the type boundary into
// chart.js — its element types can be structurally incompatible with the
// DOM lib the template/script is checked against.
const canvasId = useId()
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

    if (!hasData.value) {
        return
    }

    const dark = isDarkMode()
    const axisColor = dark ? '#a8a29e' : '#78716c'
    const gridColor = dark ? 'rgba(168, 162, 158, 0.16)' : 'rgba(120, 113, 108, 0.16)'

    chart = new Chart(canvasId, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: props.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: resolveColor(dataset.color),
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 4,
                pointBackgroundColor: resolveColor(dataset.color),
                pointBorderColor: dark ? '#1c1917' : '#ffffff',
                pointBorderWidth: 1.5,
                tension: 0.25,
                fill: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: prefersReducedMotion() ? 0 : 450,
            },
            interaction: {
                mode: 'index',
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
                    grid: {
                        color: 'transparent',
                        display: false,
                    },
                    ticks: {
                        color: axisColor,
                        maxTicksLimit: 7,
                        autoSkip: true,
                        precision: 0,
                        font: {
                            family: 'Plus Jakarta Sans, sans-serif',
                            size: 10,
                        },
                    },
                    border: {
                        color: gridColor,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor,
                        display: true,
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

watch(() => [props.type, props.locale, props.labels, props.datasets, hasData.value], renderChart, { deep: true, flush: 'post' })

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
        :aria-label="hasData ? chartLabel : emptyText"
        class="min-w-0"
    >
        <p class="sr-only">{{ accessibleSummary }}</p>
        <div v-if="hasData" class="relative h-64 w-full sm:h-72">
            <canvas :id="canvasId" aria-hidden="true"></canvas>
        </div>
        <div v-else class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-stone-200 px-6 text-center text-sm text-stone-500 dark:border-stone-700 dark:text-stone-400 sm:min-h-72">
            {{ emptyText }}
        </div>
    </div>
</template>
