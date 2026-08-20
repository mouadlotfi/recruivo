<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'
import SearchAutocomplete from '../Search/Autocomplete.vue'

// Minimal structural contract (focus only) so callers can pass any element
// type — template refs may be checked against a different DOM lib than this
// component's — and the modal only ever calls .focus() on it.
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

// The autocomplete input lives inside the child SearchAutocomplete component;
// the dialog's first input is the search field — not the close button.
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
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[10050] flex min-h-full items-start justify-center overflow-y-auto bg-stone-950/60 p-3 sm:items-center sm:p-6"
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
                class="w-full max-w-xl overflow-y-auto rounded-2xl border border-stone-200 bg-white p-5 shadow-2xl outline-none dark:border-stone-700 dark:bg-stone-900 sm:p-6"
                :class="'max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)]'"
                tabindex="-1"
                @keydown="handleTab"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 :id="titleId" class="text-lg font-semibold text-stone-900 dark:text-white">{{ t('search') }}</h2>
                        <p :id="hintId" class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ t('search_hint') }}</p>
                    </div>
                    <button
                        type="button"
                        :aria-label="t('close_search')"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-500 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-white"
                        @click="close"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
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
                    class="mt-6"
                    @submit="submitSearch"
                />
            </section>
        </div>
    </Teleport>
</template>