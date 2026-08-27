<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'

const page = usePage<PageProps>()
const { t } = useTranslation()

// Flash message alerts with 5-second auto-dismiss and manual close.
type Alert = { kind: 'success' | 'error'; message: string }

const alerts = ref<Alert[]>([])

const styles: Record<Alert['kind'], { box: string; button: string }> = {
    success: {
        box: 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200',
        button: 'text-green-600 dark:text-green-400 focus-visible:ring-green-500',
    },
    error: {
        box: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200',
        button: 'text-red-600 dark:text-red-400 focus-visible:ring-red-500',
    },
}

let timer: number | undefined

const sync = () => {
    const flash = page.props.flash ?? {}
    alerts.value = []
    if (flash.success) alerts.value.push({ kind: 'success', message: flash.success })
    if (flash.error) alerts.value.push({ kind: 'error', message: flash.error })

    window.clearTimeout(timer)
    if (alerts.value.length) {
        timer = window.setTimeout(() => (alerts.value = []), 5000)
    }
}

const dismiss = (alert: Alert) => {
    alerts.value = alerts.value.filter((a) => a !== alert)
}

watch(() => [page.props.flash?.success, page.props.flash?.error] as const, sync)
onMounted(sync)
onBeforeUnmount(() => window.clearTimeout(timer))
</script>

<template>
    <TransitionGroup
        name="toast"
        tag="div"
        aria-live="polite"
        class="pointer-events-none fixed bottom-20 left-1/2 z-50 flex w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 flex-col gap-3 sm:bottom-6"
    >
        <div
            v-for="alert in alerts"
            :key="`${alert.kind}:${alert.message}`"
            data-alert
            aria-atomic="true"
            :role="alert.kind === 'error' ? 'alert' : 'status'"
            :class="['pointer-events-auto rounded-xl border px-4 py-2.5 shadow-lg shadow-stone-900/10', styles[alert.kind].box]"
        >
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1 text-sm leading-5 sm:text-base sm:leading-6">{{ alert.message }}</div>
                <button
                    type="button"
                    :aria-label="t('dismiss')"
                    :class="['inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition hover:bg-black/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1', styles[alert.kind].button]"
                    @click="dismiss(alert)"
                >
                    <span class="sr-only">{{ t('dismiss') }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </TransitionGroup>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(0.5rem);
}
</style>
