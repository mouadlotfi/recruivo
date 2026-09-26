<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import ExpandedTextarea from '../../../Components/Applications/ExpandedTextarea.vue'
import type { PageProps, RecruiterNoteTemplate } from '../../../types'

interface TemplateFormData {
    name: string
    body: string
}

const props = defineProps<{
    templates: RecruiterNoteTemplate[]
    back_url: string
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string) => `/${locale.value}${path}`

const createForm = useForm<TemplateFormData>({
    name: '',
    body: '',
})

const editForms: Record<number, ReturnType<typeof useForm<TemplateFormData>>> = {}

for (const template of props.templates) {
    editForms[template.id] = useForm<TemplateFormData>({
        name: template.name,
        body: template.body,
    })
}

const submitCreate = () => {
    createForm.post(localeUrl('/recruiter/note-templates'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    })
}

const submitEdit = (template: RecruiterNoteTemplate) => {
    editForms[template.id]?.put(template.update_url, {
        preserveScroll: true,
    })
}

const deleteTemplate = (template: RecruiterNoteTemplate) => {
    if (!window.confirm(props.labels.delete_template_confirm)) {
        return
    }

    router.delete(template.destroy_url, { preserveScroll: true })
}
</script>

<template>
    <Head :title="labels.note_templates" />

    <AppLayout>
        <div class="space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ labels.note_templates }}</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ labels.note_templates_subtitle }}</p>
                </div>
                <Link
                    :href="back_url"
                    class="inline-flex shrink-0 items-center justify-center self-start rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    {{ labels.back_to_jobs_list }}
                </Link>
            </div>

            <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ labels.new_template }}</h2>
                <form class="mt-4 space-y-4" @submit.prevent="submitCreate">
                    <div>
                        <label for="template-name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.template_name }}</label>
                        <input
                            id="template-name"
                            v-model="createForm.name"
                            name="name"
                            type="text"
                            maxlength="100"
                            class="mt-1 w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        >
                        <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ createForm.errors.name }}</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="template-body" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.template_body }}</label>
                            <ExpandedTextarea
                                v-model="createForm.body"
                                :title="labels.expand_template_body"
                                :expand-label="labels.expand"
                                :cancel-label="labels.cancel"
                                :done-label="labels.done"
                                :close-label="labels.close"
                                :maxlength="2000"
                            />
                        </div>
                        <textarea
                            id="template-body"
                            v-model="createForm.body"
                            name="body"
                            rows="4"
                            maxlength="2000"
                            class="mt-1 w-full resize-y rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        ></textarea>
                        <p v-if="createForm.errors.body" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ createForm.errors.body }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-stone-900"
                    >
                        {{ labels.save_template }}
                    </button>
                </form>
            </section>

            <p v-if="templates.length === 0" class="text-sm text-stone-500 dark:text-stone-400">{{ labels.no_templates_yet }}</p>
            <div v-else class="space-y-4">
                <article
                    v-for="template in templates"
                    :key="template.id"
                    class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60"
                >
                    <form class="space-y-4" @submit.prevent="submitEdit(template)">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label :for="`name-${template.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.template_name }}</label>
                                <input
                                    :id="`name-${template.id}`"
                                    v-model="editForms[template.id].name"
                                    name="name"
                                    type="text"
                                    maxlength="100"
                                    class="mt-1 w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                                >
                                <p v-if="editForms[template.id].errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForms[template.id].errors.name }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between gap-3">
                                    <label :for="`body-${template.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.template_body }}</label>
                                    <ExpandedTextarea
                                        v-model="editForms[template.id].body"
                                        :title="labels.expand_template_body"
                                        :expand-label="labels.expand"
                                        :cancel-label="labels.cancel"
                                        :done-label="labels.done"
                                        :close-label="labels.close"
                                        :maxlength="2000"
                                    />
                                </div>
                                <textarea
                                    :id="`body-${template.id}`"
                                    v-model="editForms[template.id].body"
                                    name="body"
                                    rows="3"
                                    maxlength="2000"
                                    class="mt-1 w-full resize-y rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                                ></textarea>
                                <p v-if="editForms[template.id].errors.body" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForms[template.id].errors.body }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="submit"
                                :disabled="editForms[template.id].processing"
                                class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-stone-900"
                            >
                                {{ labels.update_template }}
                            </button>
                        </div>
                    </form>
                    <form class="mt-4 inline-flex" @submit.prevent="deleteTemplate(template)">
                        <button
                            type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-red-100 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                        >
                            {{ labels.delete_template }}
                        </button>
                    </form>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
