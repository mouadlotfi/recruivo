<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import Alert from './Alert.vue'

const page = usePage<PageProps>()
const { t } = useTranslation()

type Flash = { kind: 'success' | 'error'; message: string }

const alerts = ref<Flash[]>([])

let timer: number | undefined

const sync = () => {
    const flash = page.props.flash
    alerts.value = []
    if (flash.success) alerts.value.push({ kind: 'success', message: flash.success })
    if (flash.error) alerts.value.push({ kind: 'error', message: flash.error })

    window.clearTimeout(timer)
    if (alerts.value.length) {
        timer = window.setTimeout(() => (alerts.value = []), 5000)
    }
}

const dismiss = (alert: Flash) => {
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
        <Alert
            v-for="alert in alerts"
            :key="`${alert.kind}:${alert.message}`"
            data-alert
            aria-atomic="true"
            :kind="alert.kind"
            :role="alert.kind === 'error' ? 'alert' : 'status'"
            dismissible
            elevated
            :dismiss-label="t('dismiss')"
            class="pointer-events-auto"
            @dismiss="dismiss(alert)"
        >{{ alert.message }}</Alert>
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
