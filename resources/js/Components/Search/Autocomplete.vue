<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'

interface SearchSuggestionItem {
    title: string
    subtitle?: string
    url: string
}

interface SearchSuggestionSection {
    type: string
    label: string
    items: SearchSuggestionItem[]
}

interface SearchSuggestionsResponse {
    query: string
    sections: SearchSuggestionSection[]
    search_url: string
}

interface AutocompleteLabels {
    search?: string
    search_placeholder?: string
    clear_search?: string
    search_all_results?: string
    recent_searches?: string
    remove_recent_search?: string
    no_search_suggestions?: string
    search_error?: string
    suggestions_available?: string
    loading?: string
}

interface FlatOption {
    id: string
    label: string
    subtitle?: string
    url?: string
}

const props = withDefaults(defineProps<{
    modelValue: string
    searchUrl: string
    labels?: AutocompleteLabels
    inputId?: string
}>(), {
    labels: () => ({}),
})

const emit = defineEmits<{
    'update:modelValue': [value: string]
    submit: []
}>()

const page = usePage<PageProps>()

// All user-visible strings come from Laravel: the page surface passes the
// backend's SEARCH_PAGE_LABEL_KEYS as `labels`, and every surface can fall
// back to the shared shell translations (HandleInertiaRequests) which ship
// in both locales. The final fallback is the key itself — the same behavior
// as Laravel's `__()` — so a missing wiring is visible, never silently
// English.
const label = (key: keyof AutocompleteLabels): string =>
    props.labels?.[key]
    ?? page.props.translations?.[page.props.locale]?.[key]
    ?? key

// LAN-insecure-context safe id generation: a counter is used instead of the
// web-crypto random-UUID API, which is unavailable on http:// origins served on a LAN.
let idCounter = 0
const nextSearchId = (prefix = 'search'): string => `${prefix}-${++idCounter}`

const inputId = props.inputId ?? nextSearchId('search-input')
const listboxId = nextSearchId('search-listbox')
const announceId = nextSearchId('search-status')

// Sentinels stored in localStorage are HTML-escaped so any other consumer
// (bookmarks, extensions, old surfaces) can safely reuse the list.
const RECENT_KEY = 'recruivo:recent-searches'

const ESCAPE_MAP: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
}

const UNESCAPE_MAP: Record<string, string> = {
    amp: '&',
    lt: '<',
    gt: '>',
    quot: '"',
    '#39': "'",
}

const escapeHtml = (value: string): string => value.replace(/[&<>"']/g, (char) => ESCAPE_MAP[char] ?? char)

const unescapeHtml = (value: string): string =>
    value.replace(/&(amp|lt|gt|quot|#39);/g, (match, entity: string) => UNESCAPE_MAP[entity] ?? match)

const loadRecent = (): string[] => {
    try {
        const raw = window.localStorage.getItem(RECENT_KEY)
        if (!raw) return []
        const parsed: unknown = JSON.parse(raw)
        if (!Array.isArray(parsed)) return []
        return parsed.filter((entry): entry is string => typeof entry === 'string')
    } catch {
        // Storage unavailable (private mode) — recents stay empty for the session.
        return []
    }
}

const persistRecent = (terms: string[]) => {
    try {
        window.localStorage.setItem(RECENT_KEY, JSON.stringify(terms))
    } catch {
        // Storage unavailable — silently skip persistence.
    }
}

const rememberSearch = (term: string) => {
    const cleaned = escapeHtml(term)
    if (!cleaned) return
    recentSearches.value = [cleaned, ...recentSearches.value.filter((stored) => stored !== cleaned)].slice(0, 5)
    persistRecent(recentSearches.value)
}

const removeRecentSearch = (term: string) => {
    recentSearches.value = recentSearches.value.filter((stored) => stored !== term)
    persistRecent(recentSearches.value)
}

const recentSearches = ref<string[]>(loadRecent())

const input = ref<HTMLInputElement | null>(null)
const focused = ref(false)
const activeIndex = ref(-1)
const suggestions = ref<SearchSuggestionSection[]>([])
const isLoading = ref<boolean>(false)
const errorMessage = ref<string>('')

const flatOptions = computed<FlatOption[]>(() => {
    if (props.modelValue.trim()) {
        return suggestions.value.flatMap((section, sectionIndex) =>
            section.items.map((item, itemIndex) => ({
                id: `${listboxId}-option-${sectionIndex}-${itemIndex}`,
                label: item.title,
                subtitle: item.subtitle,
                url: item.url,
            })),
        )
    }
    return recentSearches.value.map((term, index) => ({
        id: `${listboxId}-recent-${index}`,
        label: unescapeHtml(term),
    }))
})

const listboxOpen = computed(() => {
    if (!focused.value) return false
    if (props.modelValue.trim()) return true
    return recentSearches.value.length > 0
})

const activeDescendant = computed(() => (activeIndex.value >= 0 ? flatOptions.value[activeIndex.value]?.id : undefined))

const flatIndexFor = (sectionIndex: number, itemIndex: number): number =>
    suggestions.value.slice(0, sectionIndex).reduce((total, section) => total + section.items.length, 0) + itemIndex

const announcement = computed(() => {
    if (errorMessage.value) return errorMessage.value
    if (isLoading.value) return label('loading')
    if (!listboxOpen.value) return ''
    const option = activeIndex.value >= 0 ? flatOptions.value[activeIndex.value] : undefined
    if (option) return escapeHtml(option.label)
    const count = suggestions.value.reduce((total, section) => total + section.items.length, 0)
    if (count > 0) return label('suggestions_available').replace(':count', String(count))
    return escapeHtml(label('no_search_suggestions'))
})

// Only http/https URLs may be visited; every other scheme (javascript:, etc.)
// is rejected so suggestion payloads can never drive arbitrary navigation.
const safeUrl = (url: string): string => {
    try {
        const resolved = new URL(url, window.location.href)
        if (resolved.protocol === 'http:' || resolved.protocol === 'https:') return resolved.href
    } catch {
        // Malformed URL — treated as unsafe.
    }
    return ''
}

// Only the URL is consumed for navigation, so the parameter is narrowed to
// that contract — callers may pass either a FlatOption or a raw suggestion item.
const visitOption = (option: { url?: string }) => {
    if (!option.url) return
    const href = safeUrl(option.url)
    if (!href) return
    router.visit(href, { preserveScroll: true })
}

const chooseRecent = (term: string) => {
    emit('update:modelValue', term)
    emit('submit')
}

const submitCurrent = () => {
    const term = props.modelValue.trim()
    if (!term) return
    rememberSearch(term)
    emit('submit')
}

const onInput = (event: Event) => {
    const target = event.target
    if (!(target instanceof HTMLInputElement)) return
    emit('update:modelValue', target.value)
}

const clearSearch = () => {
    emit('update:modelValue', '')
    activeIndex.value = -1
    nextTick(() => input.value?.focus())
}

const onFocus = () => {
    focused.value = true
}

const onBlur = () => {
    window.setTimeout(() => {
        focused.value = false
        activeIndex.value = -1
    }, 120)
}

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault()
        if (listboxOpen.value && flatOptions.value.length > 0) {
            activeIndex.value = (activeIndex.value + 1) % flatOptions.value.length
        } else {
            activeIndex.value = 0
        }
        return
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault()
        if (activeIndex.value <= 0) activeIndex.value = -1
        else activeIndex.value -= 1
        return
    }
    if (event.key === 'Enter') {
        const option = activeIndex.value >= 0 ? flatOptions.value[activeIndex.value] : undefined
        if (option?.url) {
            event.preventDefault()
            visitOption(option)
            return
        }
        if (option && !option.url) {
            event.preventDefault()
            chooseRecent(option.label)
            return
        }
        // No highlighted option: submit the typed query. The form's native
        // Enter-submit must not also fire, so the default is suppressed.
        event.preventDefault()
        submitCurrent()
        return
    }
    if (event.key === 'Escape') {
        if (listboxOpen.value) {
            event.preventDefault()
            event.stopPropagation()
            activeIndex.value = -1
            focused.value = false
            input.value?.blur()
        }
    }
}

let debounceTimer: number | undefined
let controller: AbortController | null = null

const fetchSuggestions = (term: string) => {
    if (controller) controller.abort()
    const current = new AbortController()
    controller = current
    isLoading.value = true
    errorMessage.value = ''

    fetch(`/api/search/suggestions?q=${encodeURIComponent(term)}`, { signal: current.signal })
        .then((response) => {
            if (!response.ok) throw new Error(`suggestions request failed: ${response.status}`)
            return response.json() as Promise<SearchSuggestionsResponse>
        })
        .then((data) => {
            suggestions.value = Array.isArray(data.sections) ? data.sections : []
        })
        .catch((error) => {
            if (error instanceof DOMException && error.name === 'AbortError') {
                // A newer request superseded this one (or the component
                // unmounted) — aborts must never surface as an error.
                return
            }
            suggestions.value = []
            errorMessage.value = label('search_error')
        })
        .finally(() => {
            // Only the newest request may clear the loading flag; a superseded
            // request finishing later must not hide a still-running fetch.
            if (controller === current) isLoading.value = false
        })
}

watch(
    () => props.modelValue,
    (value) => {
        window.clearTimeout(debounceTimer)
        const term = value.trim()
        activeIndex.value = -1
        if (!term) {
            if (controller) controller.abort()
            controller = null
            suggestions.value = []
            isLoading.value = false
            errorMessage.value = ''
            return
        }
        debounceTimer = window.setTimeout(() => fetchSuggestions(term), 180)
    },
    { immediate: true },
)

onBeforeUnmount(() => {
    window.clearTimeout(debounceTimer)
    if (controller) controller.abort()
    controller = null
    isLoading.value = false
})
</script>

<template>
    <form class="relative w-full" @submit.prevent="submitCurrent">
        <label :for="inputId" class="sr-only">{{ label('search_placeholder') }}</label>
        <input
            :id="inputId"
            ref="input"
            type="search"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="listboxOpen"
            :aria-controls="listboxId"
            :aria-activedescendant="activeDescendant"
            :value="modelValue"
            :placeholder="label('search_placeholder')"
            autocomplete="off"
            class="search-input w-full rounded-2xl border border-stone-200 bg-white py-3.5 pl-12 pr-36 text-base text-stone-900 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-200/60 dark:border-stone-700 dark:bg-stone-950 dark:text-white dark:focus:border-amber-500 dark:focus:ring-amber-500/15 sm:py-4 sm:text-lg"
            @input="onInput"
            @keydown="onKeydown"
            @focus="onFocus"
            @blur="onBlur"
        >
        <svg class="pointer-events-none absolute inset-y-0 left-4 my-auto h-5 w-5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>

        <div data-search-actions class="absolute inset-y-0 right-2 my-auto flex items-center gap-1">
            <button
                v-if="modelValue"
                type="button"
                data-search-clear
                class="h-10 w-10 shrink-0 items-center justify-center text-stone-400 transition-colors hover:text-stone-700 focus:outline-none focus-visible:rounded-full focus-visible:ring-2 focus-visible:ring-amber-400 dark:text-stone-500 dark:hover:text-stone-200"
                :aria-label="label('clear_search')"
                @mousedown.prevent
                @click="clearSearch"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <button
                type="submit"
                class="inline-flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-amber-600 px-3 text-sm font-semibold text-white transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 dark:hover:bg-amber-500/90"
            >
                {{ label('search') }}
            </button>
        </div>

        <p :id="announceId" class="sr-only" aria-live="polite" v-text="announcement"></p>

        <div
            v-if="listboxOpen"
            :id="listboxId"
            role="listbox"
            class="absolute inset-x-0 top-full z-50 mt-2 max-h-80 w-full overflow-y-auto rounded-2xl border border-stone-200 bg-white p-2 shadow-xl dark:border-stone-700 dark:bg-stone-900"
        >
            <template v-if="props.modelValue.trim()">
                <div
                    v-if="isLoading"
                    class="rounded-lg px-3 py-2.5 text-sm text-stone-500 dark:text-stone-400"
                    role="status"
                >
                    {{ label('loading') }}
                </div>
                <div
                    v-else-if="errorMessage"
                    class="rounded-lg px-3 py-2.5 text-sm text-red-600 dark:text-red-400"
                    role="status"
                >
                    {{ errorMessage }}
                </div>
                <template v-else>
                    <template v-for="(section, sectionIndex) in suggestions" :key="`${section.type}-${sectionIndex}`">
                        <p
                            v-if="section.label"
                            class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400"
                        >
                            {{ section.label }}
                        </p>
                        <div
                            v-for="(item, itemIndex) in section.items"
                            :key="`${sectionIndex}-${itemIndex}`"
                            role="option"
                            :id="`${listboxId}-option-${sectionIndex}-${itemIndex}`"
                            :aria-selected="activeIndex === flatIndexFor(sectionIndex, itemIndex)"
                            class="cursor-pointer rounded-lg px-3 py-2.5 text-left text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                            :class="activeIndex === flatIndexFor(sectionIndex, itemIndex) && 'bg-amber-50 dark:bg-amber-500/10'"
                            @mousedown.prevent
                            @click="visitOption(item)"
                        >
                            <span class="block font-medium">{{ item.title }}</span>
                            <span v-if="item.subtitle" class="block text-xs text-stone-500 dark:text-stone-400">{{ item.subtitle }}</span>
                        </div>
                    </template>

                    <div v-if="!suggestions.length" class="rounded-lg px-3 py-2.5 text-sm text-stone-500 dark:text-stone-400">
                        {{ label('no_search_suggestions') }}
                    </div>
                </template>

                <div class="mt-1 border-t border-stone-100 pt-1 dark:border-stone-800">
                    <button
                        type="button"
                        class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:text-amber-300 dark:hover:bg-amber-500/10"
                        @mousedown.prevent
                        @click="submitCurrent"
                    >
                        {{ label('search_all_results') }}
                    </button>
                </div>
            </template>
            <template v-else>
                <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                    {{ label('recent_searches') }}
                </p>
                <div
                    v-for="(term, index) in recentSearches"
                    :key="`recent-${index}`"
                    role="option"
                    :id="`${listboxId}-recent-${index}`"
                    :aria-selected="activeIndex === index"
                    class="group flex w-full cursor-pointer items-center rounded-lg text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                    :class="activeIndex === index && 'bg-amber-50 dark:bg-amber-500/10'"
                    @mousedown.prevent
                    @click="chooseRecent(unescapeHtml(term))"
                >
                    <span class="pointer-events-none flex-1 truncate px-3 py-2.5">{{ unescapeHtml(term) }}</span>
                    <button
                        type="button"
                        data-remove-recent-search
                        class="mr-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-stone-400 transition-colors hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:text-stone-500 dark:hover:text-red-400"
                        :aria-label="`${label('remove_recent_search')}: ${unescapeHtml(term)}`"
                        @click.stop="removeRecentSearch(term)"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>
    </form>
</template>