<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

type DashboardChartDataset = {
    label: string
    data: number[]
    color: string
}

const props = defineProps<{
    type: 'line' | 'bar'
    labels: string[]
    datasets: DashboardChartDataset[]
    ariaLabel: string
    emptyText: string
}>()

const canvas = ref<HTMLCanvasElement | null>(null)
const hasData = computed(() => props.datasets.some((dataset) => dataset.data.some((value) => value > 0)))
const summary = computed(() => props.datasets
    .map((dataset) => `${dataset.label}: ${dataset.data.reduce((total, value) => total + value, 0).toLocaleString()}`)
    .join('; '))

let chart: Chart | null = null
let themeObserver: MutationObserver | null = null

const isDarkMode = () => document.documentElement.classList.contains('dark')
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches

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

    chart = new Chart(canvas.value, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: props.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: dataset.color,
                backgroundColor: props.type === 'bar' ? `${dataset.color}b8` : 'transparent',
                borderWidth: props.type === 'line' ? 2 : 1,
                borderRadius: props.type === 'bar' ? 3 : 0,
                pointRadius: props.type === 'line' ? 2 : 0,
                pointHoverRadius: props.type === 'line' ? 4 : 0,
                pointBackgroundColor: dataset.color,
                pointBorderColor: dark ? '#1c1917' : '#ffffff',
                pointBorderWidth: props.type === 'line' ? 1.5 : 0,
                tension: props.type === 'line' ? 0.25 : 0,
                fill: false,
                maxBarThickness: 24,
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
                        label: (item) => `${item.dataset.label}: ${Number(item.raw).toLocaleString()}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: axisColor,
                        maxTicksLimit: props.type === 'bar' ? 8 : 7,
                        autoSkip: true,
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

watch(() => [props.type, props.labels, props.datasets, hasData.value], renderChart, { deep: true })

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
        :aria-label="ariaLabel"
        class="min-w-0"
    >
        <p class="sr-only">{{ summary || emptyText }}</p>
        <div v-if="hasData" class="relative h-64 w-full sm:h-72">
            <canvas ref="canvas" aria-hidden="true"></canvas>
        </div>
        <div v-else class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-stone-200 px-6 text-center text-sm text-stone-500 dark:border-stone-700 dark:text-stone-400 sm:min-h-72">
            {{ emptyText }}
        </div>
    </div>
</template>
