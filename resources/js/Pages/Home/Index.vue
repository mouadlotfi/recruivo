<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import JobCard from '../../Components/Jobs/JobCard.vue'
import type { JobSummary, PageProps, Pagination } from '../../types'

interface HomeMetrics {
    total_roles: number
    remote_roles: number
    new_this_week: number
    active_companies: number
}

interface PreferenceModal {
    show: boolean
    categories: string[]
    selected: string[]
}

const props = defineProps<{
    jobs: JobSummary[]
    metrics: HomeMetrics
    hasPreferences: boolean
    pagination: Pagination
    preferenceModal: PreferenceModal
    firstLogin: boolean
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`
const user = computed(() => page.props.auth.user)
const isCandidate = computed(() => user.value?.roles.includes('Candidate') ?? false)
const isRecruiter = computed(() => user.value?.roles.includes('Recruiter') ?? false)

const items = ref<JobSummary[]>([...props.jobs])
watch(
    () => [props.jobs, props.pagination.current_page] as const,
    ([incoming, currentPage]) => {
        if (currentPage === 1) {
            items.value = [...incoming]
            return
        }

        const byId = new Map(items.value.map((job) => [job.id, job]))
        for (const job of incoming) byId.set(job.id, job)
        items.value = [...byId.values()]
    },
)

const hasMore = computed(() => props.pagination.next_page_url !== null)
const loadingMore = ref(false)
const loadMoreFailed = ref(false)

const loadMore = () => {
    const url = props.pagination.next_page_url
    if (!url || loadingMore.value) return

    loadingMore.value = true
    loadMoreFailed.value = false
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
            loadMoreFailed.value = true
        },
        onFinish: () => {
            loadingMore.value = false
        },
    })
}

const metricItems = computed(() => [
    { value: props.metrics.total_roles, label: props.labels.active_roles },
    { value: props.metrics.remote_roles, label: props.labels.remote_jobs },
    { value: props.metrics.new_this_week, label: props.labels.new_this_week },
    { value: props.metrics.active_companies, label: props.labels.companies_hiring },
])

const preferenceUrl = computed(() => localeUrl('/candidate/preferences'))
const showPreferences = ref(props.preferenceModal.show && isCandidate.value)
const preferenceForm = useForm<{ preferred_categories: string[] }>({
    preferred_categories: [...props.preferenceModal.selected],
})

const modalFocusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
let modalKeydownHandler: ((event: KeyboardEvent) => void) | null = null
let previouslyFocusedElement: HTMLElement | null = null
let previousBodyOverflow: string | null = null

const preferencesDialog = () => document.querySelector<HTMLElement>('[data-preferences-modal] [role="dialog"]')

const handleModalKeydown = (event: KeyboardEvent) => {
    const dialog = preferencesDialog()
    if (!dialog) return

    if (event.key === 'Escape') {
        event.preventDefault()
        showPreferences.value = false
        return
    }

    if (event.key !== 'Tab') return

    const focusable = [...dialog.querySelectorAll<HTMLElement>(modalFocusableSelector)]
    if (!focusable.length) return

    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    const focusIsInsideDialog = document.activeElement instanceof HTMLElement && dialog.contains(document.activeElement)
    if (event.shiftKey && (!focusIsInsideDialog || document.activeElement === first)) {
        event.preventDefault()
        last.focus()
    } else if (!event.shiftKey && (!focusIsInsideDialog || document.activeElement === last)) {
        event.preventDefault()
        first.focus()
    }
}

const stopModalBehavior = (restoreFocus = true) => {
    if (modalKeydownHandler) {
        document.removeEventListener('keydown', modalKeydownHandler)
        modalKeydownHandler = null
    }

    if (previousBodyOverflow !== null) {
        document.body.style.overflow = previousBodyOverflow
        previousBodyOverflow = null
    }

    const trigger = previouslyFocusedElement
    previouslyFocusedElement = null
    if (restoreFocus && trigger && document.contains(trigger)) trigger.focus()
}

watch(
    showPreferences,
    async (show) => {
        if (!show) {
            stopModalBehavior()
            return
        }

        previouslyFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null
        previousBodyOverflow = document.body.style.overflow
        document.body.style.overflow = 'hidden'
        modalKeydownHandler = handleModalKeydown
        document.addEventListener('keydown', modalKeydownHandler)

        await nextTick()
        preferencesDialog()?.querySelector<HTMLElement>(modalFocusableSelector)?.focus()
    },
    { immediate: true, flush: 'post' },
)

onBeforeUnmount(() => stopModalBehavior(false))

watch(
    () => props.preferenceModal.show,
    (show) => {
        showPreferences.value = show && isCandidate.value
    },
)
watch(
    () => props.preferenceModal.selected,
    (selected) => {
        if (!preferenceForm.processing) preferenceForm.preferred_categories = [...selected]
    },
)

const savePreferences = () => {
    preferenceForm.post(preferenceUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            showPreferences.value = false
        },
    })
}

const skipPreferences = () => {
    router.post(preferenceUrl.value, { skip: '1' }, {
        preserveScroll: true,
        onSuccess: () => {
            showPreferences.value = false
        },
    })
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.title" />

        <div class="space-y-12">
            <!-- Hero Section -->
            <section class="space-y-6 text-center">
                <template v-if="!user">
                    <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                        {{ labels.hero_title_guest }}
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                        {{ labels.hero_description_guest }}
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                        <Link
                            :href="localeUrl('/register')"
                            class="inline-flex items-center justify-center rounded-full bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500"
                        >
                            {{ labels.get_started }}
                        </Link>
                        <Link
                            :href="localeUrl('/jobs')"
                            class="inline-flex items-center justify-center rounded-full border-2 border-amber-600 px-6 py-3 text-base font-semibold text-amber-600 transition hover:bg-amber-50 dark:border-amber-400 dark:text-amber-400 dark:hover:bg-amber-500/10"
                        >
                            {{ labels.browse_jobs }}
                        </Link>
                    </div>
                </template>
                <template v-else-if="isCandidate">
                    <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                        {{ props.firstLogin ? labels.hero_title_candidate_first : labels.hero_title_candidate }}
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                        {{ labels.hero_description_candidate }}
                    </p>
                </template>
                <template v-else-if="isRecruiter">
                    <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                        {{ labels.hero_title_recruiter }}
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                        {{ labels.hero_description_recruiter }}
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                        <Link
                            :href="localeUrl('/recruiter/jobs/create')"
                            class="inline-flex items-center justify-center rounded-full bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500"
                        >
                            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ labels.post_a_job }}
                        </Link>
                        <Link
                            :href="localeUrl('/recruiter/dashboard')"
                            class="inline-flex items-center justify-center rounded-full border-2 border-amber-600 px-6 py-3 text-base font-semibold text-amber-600 transition hover:bg-amber-50 dark:border-amber-400 dark:text-amber-400 dark:hover:bg-amber-500/10"
                        >
                            {{ labels.view_dashboard }}
                        </Link>
                    </div>
                </template>
                <template v-else>
                    <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                        {{ labels.hero_title }}
                    </h1>
                    <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                        {{ labels.hero_description }}
                    </p>
                </template>

                <!-- Metrics -->
                <div class="mx-auto grid max-w-4xl grid-cols-2 gap-4 pt-8 sm:grid-cols-4">
                    <div
                        v-for="metric in metricItems"
                        :key="metric.label"
                        class="rounded-xl border border-stone-200/60 bg-white/60 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40"
                    >
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ metric.value }}</div>
                        <div class="text-sm text-stone-600 dark:text-stone-400">{{ metric.label }}</div>
                    </div>
                </div>
            </section>

            <!-- Jobs Section -->
            <section class="space-y-6">
                <h2 v-if="props.hasPreferences" class="text-xl font-semibold text-stone-900 dark:text-white">
                    {{ labels.recommended_for_you }}
                </h2>

                <div v-if="items.length">
                    <div class="grid gap-6 md:grid-cols-2">
                        <JobCard v-for="job in items" :key="job.id" :job="job" :labels="labels" />
                    </div>
                    <div v-if="hasMore" class="mt-6 text-center">
                        <p v-if="loadMoreFailed" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ labels.load_more_failed }}</p>
                        <button
                            type="button"
                            :disabled="loadingMore"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-6 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
                            @click="loadMore"
                        >
                            {{ loadingMore ? labels.loading_more : labels.show_more }}
                        </button>
                    </div>
                </div>
                <div
                    v-else
                    class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40"
                >
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_roles_title }}</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.no_roles_description }}</p>
                    <div class="mt-4">
                        <Link
                            :href="localeUrl('/')"
                            class="inline-flex items-center justify-center rounded-full border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:border-amber-400 hover:text-amber-500 dark:border-amber-500/40 dark:text-amber-300 dark:hover:border-amber-400/60"
                        >
                            {{ labels.show_all_opportunities }}
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>

    <Teleport to="body">
        <div
            v-if="showPreferences"
            data-preferences-modal
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.esc="showPreferences = false"
        >
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div
                    class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity"
                    @click="showPreferences = false"
                ></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="preferences-modal-title"
                    class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                    @click.stop
                >
                    <div class="p-6 sm:p-8">
                        <div class="text-center">
                            <h3 id="preferences-modal-title" class="text-xl font-semibold text-stone-900 dark:text-white">
                                {{ labels.preferences_modal_title }}
                            </h3>
                            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                                {{ labels.preferences_modal_help }}
                            </p>
                        </div>
                        <form method="post" :action="preferenceUrl" class="mt-5" @submit.prevent="savePreferences">
                            <div class="grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                                <label
                                    v-for="category in preferenceModal.categories"
                                    :key="category"
                                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-stone-200 bg-white/60 px-3 py-2 text-sm text-stone-700 transition hover:border-amber-300 dark:border-stone-700 dark:bg-stone-900/60 dark:text-stone-200"
                                >
                                    <input
                                        v-model="preferenceForm.preferred_categories"
                                        type="checkbox"
                                        name="preferred_categories[]"
                                        :value="category"
                                        class="h-5 w-5 rounded border-stone-300 text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:border-stone-600 dark:bg-stone-800 dark:focus-visible:ring-offset-stone-950"
                                    >
                                    {{ category }}
                                </label>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="preferenceForm.processing"
                                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:opacity-60 dark:focus-visible:ring-offset-stone-950"
                                >
                                    {{ labels.save_preferences }}
                                </button>
                            </div>
                        </form>
                        <form method="post" :action="preferenceUrl" class="mt-3 text-center" @submit.prevent="skipPreferences">
                            <button
                                type="submit"
                                class="text-sm font-medium text-stone-500 transition hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200"
                            >
                                {{ labels.skip_for_now }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
