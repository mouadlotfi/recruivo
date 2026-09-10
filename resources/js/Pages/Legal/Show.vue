<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

type LegalSection = { heading: string; body: string }

const props = defineProps<{
    document: { title: string; summary: string; sections: LegalSection[]; updated: string }
    labels: Record<string, string>
    contact_email: string
    /** Consumed here so it never falls through to the DOM; the shell renders it. */
    meta: { title: string; description: string }
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`
</script>

<template>
    <AppLayout>
        <Head :title="document.title" :description="meta.description" />

        <div class="mx-auto max-w-3xl">
            <header class="mb-10 border-b border-stone-200 pb-6 dark:border-stone-800">
                <h1 class="font-display text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl dark:text-white">
                    {{ document.title }}
                </h1>
                <p class="mt-3 text-base text-stone-600 dark:text-stone-400">{{ document.summary }}</p>
                <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ labels.updated }}</p>
            </header>

            <div class="space-y-8">
                <section v-for="(section, index) in document.sections" :key="index">
                    <h2 class="font-display text-xl font-semibold text-stone-900 dark:text-white">
                        {{ section.heading }}
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600 sm:text-base dark:text-stone-300">
                        {{ section.body }}
                    </p>
                </section>
            </div>

            <footer class="mt-12 border-t border-stone-200 pt-6 dark:border-stone-800">
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    {{ labels.questions }}
                </p>
                <a
                    :href="`mailto:${contact_email}`"
                    class="mt-3 inline-block font-medium text-amber-600 transition hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ contact_email }}
                </a>
                <div class="mt-6">
                    <Link
                        :href="localeUrl('/')"
                        class="text-sm font-medium text-stone-600 transition hover:text-stone-900 dark:text-stone-400 dark:hover:text-white"
                    >
                        ← {{ labels.back_home }}
                    </Link>
                </div>
            </footer>
        </div>
    </AppLayout>
</template>
