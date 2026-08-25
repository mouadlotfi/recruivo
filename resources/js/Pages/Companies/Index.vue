<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import type { CompanyCardSummary, PageProps, Pagination } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'
import CompanyCard from '../../Components/Companies/CompanyCard.vue'

const props = defineProps<{
    companies: CompanyCardSummary[]
    pagination: Pagination
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

// Appends next-page items while resetting on initial page loads.
const items = ref<CompanyCardSummary[]>([...props.companies])
watch(
    () => props.companies,
    (incoming) => {
        if (props.pagination.current_page === 1) {
            items.value = [...incoming]
            return
        }
        const byId = new Map(items.value.map((company) => [company.id, company]))
        for (const company of incoming) byId.set(company.id, company)
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
</script>

<template>
    <AppLayout>
        <Head :title="labels.browse_companies_title" />
        <div class="space-y-8">
            <header>
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ labels.browse_companies_title }}</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.browse_companies_subtitle }}</p>
            </header>

            <div>
                <div v-if="items.length" class="grid gap-6 md:grid-cols-2">
                    <CompanyCard v-for="company in items" :key="company.id" :company="company" :labels="labels" />
                </div>

                <div
                    v-else
                    class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40"
                >
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.no_companies_found_index }}</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.check_back_for_companies }}</p>
                </div>

                <div v-if="hasMore && items.length" class="mt-6 text-center">
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
        </div>
    </AppLayout>
</template>