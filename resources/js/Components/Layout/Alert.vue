<script setup lang="ts">
type AlertKind = 'success' | 'error' | 'info' | 'warning'

const props = withDefaults(
    defineProps<{
        kind: AlertKind
        role?: 'status' | 'alert'
        dismissible?: boolean
        dismissLabel?: string
        elevated?: boolean
    }>(),
    { role: 'status', dismissible: false, elevated: false },
)

const emit = defineEmits<{ dismiss: [] }>()

const SURFACES: Record<AlertKind, string> = {
    success: 'border-green-200 dark:border-green-800/70',
    error: 'border-red-200 dark:border-red-800/70',
    info: 'border-blue-200 dark:border-blue-800/70',
    warning: 'border-amber-200 dark:border-amber-800/70',
}

const BADGES: Record<AlertKind, string> = {
    success: 'bg-green-600 text-white',
    error: 'bg-red-600 text-white',
    info: 'bg-blue-600 text-white',
    warning: 'bg-amber-500 text-stone-900',
}
</script>

<template>
    <div
        :role="props.role"
        :class="[
            'flex items-start gap-3 rounded-xl border bg-white p-4 text-sm text-stone-800 dark:bg-stone-900 dark:text-stone-100',
            SURFACES[props.kind],
            props.elevated ? 'shadow-xl shadow-stone-900/10 dark:shadow-black/40' : 'shadow-sm',
        ]"
    >
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="BADGES[props.kind]">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    v-if="props.kind === 'success'"
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                />
                <path
                    v-else-if="props.kind === 'error'"
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                />
                <path
                    v-else-if="props.kind === 'info'"
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                />
                <path
                    v-else
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                />
            </svg>
        </span>
        <div class="min-w-0 flex-1 leading-5 sm:leading-6"><slot /></div>
        <button
            v-if="props.dismissible"
            type="button"
            :aria-label="props.dismissLabel"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-800"
            @click="emit('dismiss')"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                />
            </svg>
        </button>
    </div>
</template>
