<script setup lang="ts">
import { nextTick, ref, useId, watch } from 'vue'

// Port of resources/views/components/expanded-textarea.blade.php to Vue.
// Controlled component: the trigger button + modal live here, the real
// textarea stays in the parent (v-model). Done commits the draft via
// update:modelValue; Cancel/Escape discard it. Minimal focus handling:
// focus the modal textarea on open, restore the trigger on close.
const props = defineProps<{
    title: string
    expandLabel: string
    cancelLabel: string
    doneLabel: string
    closeLabel: string
    modelValue: string
    placeholder?: string
    maxlength?: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

// Unique per instance so N cards on one page never share a DOM id.
const titleId = useId()

const open = ref(false)
const draft = ref(props.modelValue)
const trigger = ref<HTMLButtonElement | null>(null)
const modalTextarea = ref<HTMLTextAreaElement | null>(null)

const openModal = () => {
    draft.value = props.modelValue
    open.value = true
    nextTick(() => modalTextarea.value?.focus())
}

const closeModal = () => {
    open.value = false
    nextTick(() => trigger.value?.focus())
}

// Lock background scroll while the modal is open (port of Blade x-trap.noscroll).
watch(open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
})

// Minimal Tab focus trap: cycle focus within the modal (port of Blade x-trap).
const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
const trapTab = (e: KeyboardEvent) => {
    if (e.key !== 'Tab' || !open.value) return
    const modal = e.currentTarget as HTMLElement
    const focusables = Array.from(modal.querySelectorAll<HTMLElement>(FOCUSABLE)).filter((el) => el.offsetParent !== null)
    if (!focusables.length) return
    const first = focusables[0]
    const last = focusables[focusables.length - 1]
    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault()
        last.focus()
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault()
        first.focus()
    }
}

const commit = () => {
    emit('update:modelValue', draft.value)
    closeModal()
}
</script>

<template>
    <div data-expanded-textarea class="inline-flex">
        <button
            ref="trigger"
            type="button"
            :aria-expanded="open"
            aria-haspopup="dialog"
            class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-medium text-stone-500 transition hover:bg-stone-100 hover:text-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-amber-400"
            @click="openModal"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
            </svg>
            <span>{{ expandLabel }}</span>
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="fixed inset-0 z-[10050]"
                @keydown.escape.window="closeModal"
                @keydown.tab="trapTab"
            >
                <div class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm" @click="closeModal"></div>

                <div class="fixed inset-0 flex items-center justify-center p-0 sm:p-6">
                    <div class="flex h-full w-full flex-col bg-white shadow-2xl dark:bg-stone-900 sm:h-auto sm:max-h-[85vh] sm:max-w-3xl sm:rounded-2xl sm:border sm:border-stone-200 dark:sm:border-stone-700">
                        <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4 dark:border-stone-700">
                            <h2 :id="titleId" class="text-base font-semibold text-stone-900 dark:text-white">
                                {{ title }}
                            </h2>
                            <button
                                type="button"
                                :aria-label="closeLabel"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-stone-500 transition hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-200"
                                @click="closeModal"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 p-4 sm:p-5">
                            <textarea
                                ref="modalTextarea"
                                v-model="draft"
                                :placeholder="placeholder"
                                :maxlength="maxlength"
                                class="h-full min-h-0 w-full resize-none rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-stone-200 px-5 py-4 dark:border-stone-700">
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-stone-600 transition hover:bg-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-300 dark:hover:bg-stone-800"
                                @click="closeModal"
                            >
                                {{ cancelLabel }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-900"
                                @click="commit"
                            >
                                {{ doneLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
