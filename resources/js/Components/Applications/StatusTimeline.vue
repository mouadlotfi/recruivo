<script setup lang="ts">
import type { TimelineEvent } from '../../types'

defineProps<{
    events: TimelineEvent[]
}>()
</script>

<template>
    <ol data-application-status-timeline class="space-y-3">
        <li v-for="(event, index) in events" :key="index" class="relative pl-6">
            <span aria-hidden="true" class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
            <p class="text-sm font-semibold text-stone-800 dark:text-stone-200">{{ event.label }}</p>
            <time :datetime="event.created_at" class="text-xs text-stone-500 dark:text-stone-400">{{ event.formatted_at }}</time>
        </li>
    </ol>
</template>
