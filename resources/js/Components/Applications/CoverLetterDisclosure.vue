<script setup lang="ts">
const props = withDefaults(defineProps<{
    content: string
    label: string
    variant?: 'candidate' | 'recruiter'
}>(), {
    variant: 'candidate',
})
</script>

<template>
    <details data-cover-letter-collapsible class="group" :class="props.variant === 'candidate' ? 'mb-3' : ''">
        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between rounded-lg bg-stone-50 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 [&::-webkit-details-marker]:hidden">
            {{ label }}
            <svg class="h-4 w-4 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </summary>
        <div
            class="mt-2 whitespace-pre-line bg-stone-50 p-4 text-sm leading-6 text-stone-700 dark:text-stone-300"
            :class="props.variant === 'candidate' ? 'rounded-lg dark:bg-stone-800' : 'rounded-xl dark:bg-stone-800/80'"
        >{{ content }}</div>
    </details>
</template>
