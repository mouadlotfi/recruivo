<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useTranslation } from '../../composables/useTranslation'

const { t } = useTranslation()

// Smooth scroll-to-top control, visible after 500px of scroll depth.
const visible = ref(false)

const updateVisibility = () => {
    visible.value = window.scrollY > 500
}

const scrollToTop = () => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' })
}

onMounted(() => {
    window.addEventListener('scroll', updateVisibility, { passive: true })
    updateVisibility()
})

onBeforeUnmount(() => window.removeEventListener('scroll', updateVisibility))
</script>

<template>
    <button
        id="scroll-to-top"
        type="button"
        :class="[
            'fixed bottom-20 right-4 z-40 h-11 w-11 items-center justify-center rounded-full border border-amber-400/40 bg-white text-amber-600 shadow-xl shadow-stone-950/10 backdrop-blur transition hover:-translate-y-0.5 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-stone-50 dark:border-amber-500/40 dark:bg-stone-900/90 dark:text-amber-300 dark:shadow-stone-950/30 dark:hover:bg-stone-800 dark:focus:ring-offset-stone-950 sm:bottom-6 sm:right-6',
            visible ? 'flex' : 'hidden',
        ]"
        :aria-label="t('scroll_to_top')"
        :title="t('scroll_to_top')"
        @click="scrollToTop"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75 12 8.25l7.5 7.5" />
        </svg>
    </button>
</template>
