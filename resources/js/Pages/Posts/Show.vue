<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { PageProps, PostDetail, PostLanguageLink } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps<{
    post: PostDetail
    placeholder_image_url: string
    index_url: string
    language_links: PostLanguageLink[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const currentLocale = computed(() => page.props.locale)

const handleImageError = (event: Event) => {
    const image = event.currentTarget as HTMLImageElement
    image.onerror = null
    image.src = props.placeholder_image_url
}
</script>

<template>
    <AppLayout>
        <Head :title="post.title" />
        <article class="mx-auto max-w-4xl">
            <div class="mb-8">
                <Link :href="index_url" class="inline-flex items-center text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ labels.back }}
                </Link>
            </div>

            <header class="mb-8">
                <h1 class="text-4xl font-bold text-stone-900 dark:text-white">{{ post.title }}</h1>

                <div class="mt-4 flex items-center gap-4 text-sm text-stone-600 dark:text-stone-400">
                    <time>{{ post.published_at_label }}</time>
                    <span>•</span>
                    <span>{{ labels.by }} {{ post.author_name }}</span>
                </div>
            </header>

            <div class="mb-8">
                <img
                    :src="post.featured_image_url"
                    :alt="post.title"
                    decoding="async"
                    class="w-full rounded-xl"
                    @error="handleImageError"
                >
            </div>

            <!-- content_html is escaped server-side before newlines are converted to <br>. -->
            <div class="prose prose-stone max-w-none dark:prose-invert" v-html="post.content_html"></div>

            <footer class="mt-12 border-t border-stone-200 pt-8 dark:border-stone-700">
                <div class="flex items-center justify-between">
                    <Link :href="index_url" class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">
                        ← {{ labels.all }}
                    </Link>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-stone-600 dark:text-stone-400">{{ labels.languages }}:</span>
                        <div class="flex gap-2">
                            <Link
                                v-for="language in language_links"
                                :key="language.locale"
                                :href="language.href"
                                class="rounded-lg px-3 py-1 text-sm font-medium transition"
                                :class="currentLocale === language.locale
                                    ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'
                                    : 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800'"
                            >
                                {{ language.locale.toUpperCase() }}
                            </Link>
                        </div>
                    </div>
                </div>
            </footer>
        </article>
    </AppLayout>
</template>
