<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    status: string
    label: string
    showDot?: boolean
}>()
const COLORS: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
    shortlisted: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
    interview: 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
    accepted: 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
    withdrawn: 'bg-stone-200 text-stone-700 dark:bg-stone-700/40 dark:text-stone-300',
}

const classes = computed(() => COLORS[props.status] ?? 'bg-stone-100 text-stone-700')
</script>

<template>
    <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs" :class="[classes, props.showDot === false ? 'font-semibold' : 'font-medium']">
        <svg v-if="props.showDot !== false" class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8" aria-hidden="true">
            <circle cx="4" cy="4" r="3" />
        </svg>
        {{ props.label }}
    </span>
</template>
