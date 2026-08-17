<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import { useTranslation } from '../../composables/useTranslation'

const props = defineProps<{
    open: boolean
    trigger?: HTMLElement | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const page = usePage<PageProps>()
const { t } = useTranslation()
const query = ref('')
const dialog = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)
const restoreFocus = ref<HTMLElement | null>(null)
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
    nextTick(() => input.value?.focus())
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
    ;(input.value ?? focusableElements()[0] ?? dialog.value).focus()
}

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

                <form class="mt-6" @submit.prevent="submitSearch">
                    <label :for="inputId" class="sr-only">{{ t('search') }}</label>
                    <div class="relative">
                        <input
                            :id="inputId"
                            ref="input"
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            :placeholder="t('search_placeholder')"
                            class="w-full rounded-xl border border-stone-200 bg-white py-3.5 pl-4 pr-40 text-base text-stone-900 shadow-sm transition placeholder:text-stone-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-200/60 dark:border-stone-700 dark:bg-stone-950 dark:text-white dark:placeholder:text-stone-500 dark:focus:border-amber-500 dark:focus:ring-amber-500/15 sm:pr-44"
                        >
                        <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                            <button
                                v-if="query"
                                type="button"
                                :aria-label="t('clear_search')"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-stone-400 transition hover:bg-stone-100 hover:text-stone-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-500 dark:hover:bg-stone-800 dark:hover:text-stone-200"
                                @click="query = ''; focusInput()"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <button
                                type="submit"
                                class="inline-flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-amber-600 px-3 text-sm font-semibold text-white transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 dark:hover:bg-amber-500/90"
                            >
                                {{ t('search') }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </Teleport>
</template>
