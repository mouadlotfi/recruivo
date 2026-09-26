<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import type { PageProps } from '../../types'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps<{
    contact_email: string
    labels: Record<string, string>
    meta: { title: string; description: string }
}>()

const page = usePage<PageProps>()
const form = useForm({
    name: '',
    email: '',
    message: '',
    contact_website_url: '',
})

const submit = () => {
    form.post(`/${page.props.locale}/contact`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <AppLayout>
        <Head :title="labels.title" :description="meta.description" />

        <div class="mx-auto max-w-2xl">
            <header class="mb-8">
                <h1 class="font-display text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl dark:text-white">
                    {{ labels.title }}
                </h1>
                <p class="mt-3 text-base text-stone-600 dark:text-stone-400">{{ labels.summary }}</p>
            </header>

            <form
                class="space-y-6 rounded-3xl border border-stone-200/70 bg-white/80 p-6 shadow-sm sm:p-8 dark:border-stone-800/60 dark:bg-stone-900/60"
                @submit.prevent="submit"
            >
                <div
                    v-if="page.props.flash?.success"
                    role="status"
                    class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200"
                >
                    <p class="font-semibold">{{ labels.sent }}</p>
                    <p class="mt-1">{{ labels.sent_description }}</p>
                </div>

                <!-- Honeypot, checked server-side. -->
                <div class="hidden" aria-hidden="true">
                    <label for="contact_website_url">Website</label>
                    <input
                        id="contact_website_url"
                        v-model="form.contact_website_url"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                    />
                </div>

                <div>
                    <label for="contact_name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                        {{ labels.name }}
                    </label>
                    <input
                        id="contact_name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        class="mt-2 block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm transition focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-950 dark:text-white dark:focus:ring-amber-500/30"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="contact_email" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                        {{ labels.email }}
                    </label>
                    <input
                        id="contact_email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-2 block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm transition focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-950 dark:text-white dark:focus:ring-amber-500/30"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="contact_message" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                        {{ labels.message }}
                    </label>
                    <textarea
                        id="contact_message"
                        v-model="form.message"
                        rows="6"
                        required
                        class="mt-2 block w-full rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 shadow-sm transition focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-950 dark:text-white dark:focus:ring-amber-500/30"
                    ></textarea>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ labels.message_hint }}</p>
                    <p v-if="form.errors.message" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.message }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus-visible:ring-offset-stone-950"
                >
                    {{ labels.send }}
                </button>
            </form>

            <section class="mt-8 rounded-3xl border border-stone-200/70 bg-white/60 p-6 sm:p-8 dark:border-stone-800/60 dark:bg-stone-900/40">
                <h2 class="font-display text-lg font-semibold text-stone-900 dark:text-white">{{ labels.direct }}</h2>
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ labels.direct_description }}</p>
                <a
                    :href="`mailto:${contact_email}`"
                    class="mt-2 inline-block font-medium text-amber-600 transition hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    {{ contact_email }}
                </a>
                <p class="mt-4 text-sm text-stone-500 dark:text-stone-400">{{ labels.response_time }}</p>
            </section>
        </div>
    </AppLayout>
</template>
