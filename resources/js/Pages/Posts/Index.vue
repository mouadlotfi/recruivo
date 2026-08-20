<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import type { Pagination, PostSummary } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps<{
    posts: PostSummary[]
    pagination: Pagination
    placeholder_image_url: string
    labels: Record<string, string>
}>()

const items = ref<PostSummary[]>([...props.posts])
watch(
    () => props.posts,
    (incoming) => {
        if (props.pagination.current_page === 1) {
            items.value = [...incoming]
            return
        }
        const byId = new Map(items.value.map((post) => [post.id, post]))
        for (const post of incoming) byId.set(post.id, post)
        items.value = [...byId.values()]
    },
)

const hasMore = () => props.pagination.next_page_url !== null
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

const handleImageError = (event: Event) => {
    const image = event.currentTarget as HTMLImageElement
    image.onerror = null
    image.src = props.placeholder_image_url
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.title" />
        <div class="mx-auto max-w-6xl">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ labels.title }}</h1>
                <p class="mt-2 text-stone-600 dark:text-stone-400">{{ labels.subtitle }}</p>
            </div>

            <div v-if="items.length">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="post in items"
                        :key="post.id"
                        class="overflow-hidden rounded-xl border border-stone-200/60 bg-white/80 shadow-sm backdrop-blur transition hover:shadow-lg dark:border-stone-700/60 dark:bg-stone-900/60"
                    >
                        <img
                            :src="post.featured_image_url"
                            :alt="post.title"
                            loading="lazy"
                            decoding="async"
                            class="h-48 w-full object-cover"
                            @error="handleImageError"
                        >

                        <div class="p-6">
                            <h2 class="text-xl font-bold text-stone-900 dark:text-white">
                                <Link :href="post.url" class="hover:text-amber-600 dark:hover:text-amber-400">
                                    {{ post.title }}
                                </Link>
                            </h2>

                            <p class="mt-3 text-stone-600 dark:text-stone-400">
                                {{ post.excerpt }}
                            </p>

                            <div class="mt-4 flex items-center justify-between text-sm text-stone-500 dark:text-stone-400">
                                <time>{{ post.published_at_label }}</time>
                                <Link :href="post.url" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
                                    {{ labels.read_more }} →
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-if="hasMore()" class="mt-6 text-center">
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

            <div v-else class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <p class="text-stone-600 dark:text-stone-400">{{ labels.empty }}</p>
            </div>
        </div>
    </AppLayout>
</template>
