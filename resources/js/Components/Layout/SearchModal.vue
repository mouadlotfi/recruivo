<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import SearchAutocomplete from '../Search/Autocomplete.vue'

interface FocusableElement {
    focus(): void
}

const props = defineProps<{
    open: boolean
    trigger?: FocusableElement | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const page = usePage<PageProps>()
const { t } = useTranslation()
const query = ref('')
const dialog = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)
const restoreFocus = ref<FocusableElement | null>(null)
const previousBodyOverflow = ref('')
const modalId = useId()
const titleId = `${modalId}-title`
const hintId = `${modalId}-hint`
const inputId = `${modalId}-input`
const searchUrl = computed(() => `/${page.props.locale}/search`)

const close = () => {
    query.value = ''
    if (props.open) emit('update:open', false)
}

const focusInput = () => {
    nextTick(() => {
        ;(firstInput() ?? focusableElements()[0] ?? dialog.value).focus()
    })
}

const onWindowKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.preventDefault()
        close()
    }
}

const onWindowFocusin = (event: FocusEvent) => {
    if (!props.open || !dialog.value) return

    const target = event.target
    if (!(target instanceof Node) || dialog.value.contains(target)) return

    event.preventDefault()
    event.stopPropagation()
    ;(firstInput() ?? input.value ?? focusableElements()[0] ?? dialog.value).focus()
}

// Focus the primary search input on open.
const firstInput = () => dialog.value?.querySelector<HTMLElement>('input:not([disabled])') ?? null

const focusableElements = () => Array.from(
    dialog.value?.querySelectorAll<HTMLElement>(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
    ) ?? [],
)

const handleTab = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return

    const elements = focusableElements()
    if (!elements.length) {
        event.preventDefault()
        return
    }

    const first = elements[0]
    const last = elements[elements.length - 1]

    if (event.shiftKey && (document.activeElement === first || !dialog.value?.contains(document.activeElement))) {
        event.preventDefault()
        last.focus()
    } else if (!event.shiftKey && (document.activeElement === last || !dialog.value?.contains(document.activeElement))) {
        event.preventDefault()
        first.focus()
    }
}

const lockBody = () => {
    previousBodyOverflow.value = document.body.style.overflow
    document.body.style.overflow = 'hidden'
}

const restoreBody = () => {
    document.body.style.overflow = previousBodyOverflow.value
}

const activate = () => {
    restoreFocus.value = props.trigger ?? (document.activeElement instanceof HTMLElement ? document.activeElement : null)
    lockBody()
    window.addEventListener('keydown', onWindowKeydown)
    window.addEventListener('focusin', onWindowFocusin, true)
    focusInput()
}

const deactivate = () => {
    query.value = ''
    window.removeEventListener('keydown', onWindowKeydown)
    window.removeEventListener('focusin', onWindowFocusin, true)
    restoreBody()
    nextTick(() => {
        restoreFocus.value?.focus()
        restoreFocus.value = null
    })
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) activate()
        else deactivate()
    },
)

onMounted(() => {
    if (props.open) activate()
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onWindowKeydown)
    window.removeEventListener('focusin', onWindowFocusin, true)
    if (props.open) restoreBody()
})

const submitSearch = () => {
    router.get(searchUrl.value, { search: query.value.trim() }, { preserveState: true, preserveScroll: true })
    close()
}

const quickCategories = [
    { label: 'Engineering', value: 'engineering' },
    { label: 'Cloud', value: 'cloud' },
    { label: 'Security', value: 'security' },
    { label: 'Data', value: 'data' },
    { label: 'Remote', value: 'remote' },
]

const pickQuickCategory = (val: string) => {
    query.value = val
    submitSearch()
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[10050] flex min-h-full items-start justify-center overflow-y-auto bg-stone-950/70 p-3 pt-12 backdrop-blur-xl sm:p-6 sm:pt-20"
            role="presentation"
            @click.self="close"
        >
            <section
                :id="modalId"
                ref="dialog"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="hintId"
                class="relative w-full max-w-2xl overflow-visible rounded-3xl border border-stone-200/90 bg-white/95 p-5 shadow-[0_25px_70px_-15px_rgba(0,0,0,0.35)] outline-none backdrop-blur-2xl dark:border-stone-800/90 dark:bg-stone-900/95 dark:shadow-[0_25px_70px_-15px_rgba(0,0,0,0.8)] sm:p-6"
                tabindex="-1"
                @keydown="handleTab"
            >
                <div class="pointer-events-none absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-amber-500/0 via-amber-500/60 to-teal-500/0"></div>

                <div class="flex items-center justify-between gap-4">
                    <h2 :id="titleId" class="font-display text-lg font-bold tracking-tight text-stone-900 dark:text-white">
                        {{ t('search') }}
                    </h2>
                    <p :id="hintId" class="sr-only">
                        {{ t('search_hint') }}
                    </p>
                    <button
                        type="button"
                        :aria-label="t('close_search')"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-stone-200/60 bg-stone-100/60 text-stone-500 transition hover:border-stone-300 hover:bg-stone-200/70 hover:text-stone-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700/60 dark:bg-stone-800/60 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:bg-stone-700/70 dark:hover:text-stone-200"
                        @click="close"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <SearchAutocomplete
                    v-model="query"
                    :search-url="searchUrl"
                    :input-id="inputId"
                    :labels="{
                        search: t('search'),
                        search_placeholder: t('search_placeholder'),
                        clear_search: t('clear_search'),
                        search_all_results: t('search_all_results'),
                        recent_searches: t('recent_searches'),
                        remove_recent_search: t('remove_recent_search'),
                        no_search_suggestions: t('no_search_suggestions'),
                        search_error: t('search_error'),
                        suggestions_available: t('suggestions_available'),
                        loading: t('loading'),
                    }"
                    class="mt-5"
                    @submit="submitSearch"
                />

                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800/80">
                    <span class="text-[11px] font-medium text-stone-400 dark:text-stone-500">Popular:</span>
                    <button
                        v-for="cat in quickCategories"
                        :key="cat.value"
                        type="button"
                        class="inline-flex items-center rounded-lg border border-stone-200/70 bg-stone-50/70 px-2 py-0.5 text-xs font-medium text-stone-600 transition hover:border-amber-400/80 hover:bg-amber-50 hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-300 dark:hover:border-amber-500/40 dark:hover:bg-amber-500/10 dark:hover:text-amber-300"
                        @click="pickQuickCategory(cat.value)"
                    >
                        {{ cat.label }}
                    </button>
                </div>
            </section>
        </div>
    </Teleport>
</template>
