<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import type { PageProps, RecruiterApplication, RecruiterNoteTemplate, ReviewFormData } from '../../types'
import ExpandedTextarea from './ExpandedTextarea.vue'

// Review form for one application. Starts at a NEUTRAL default (status '',
// empty notes, no interview pre-fill) — never pre-filled with the
// application's current state (matches the Blade contract). After a
// successful PATCH the controller redirects back; reset() puts the select
// back on the placeholder. Validation errors arrive via Inertia's shared
// `errors` prop and are shown per field.
const props = defineProps<{
    application: RecruiterApplication
    noteTemplates: RecruiterNoteTemplate[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()

const form = useForm<ReviewFormData>({
    status: '',
    interview_mode: 'onsite',
    notes: '',
    interview_at: '',
    interview_location: '',
    interview_url: '',
    interview_instructions: '',
})

// Withdrawn is candidate-owned — recruiters can never set it.
const STATUS_OPTIONS = ['pending', 'shortlisted', 'interview', 'accepted', 'rejected']

const updateUrl = `/${page.props.locale}/recruiter/applications/${props.application.id}`
const notesTextarea = ref<HTMLTextAreaElement | null>(null)

const autosizeNotes = () => {
    const textarea = notesTextarea.value
    if (!textarea) return
    textarea.style.height = 'auto'
    textarea.style.height = `${textarea.scrollHeight}px`
}

onMounted(autosizeNotes)
watch(() => form.notes, () => nextTick(autosizeNotes))

const submit = () => {
    form.patch(updateUrl, {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <label :for="`status-${application.id}`" class="block text-sm font-semibold text-stone-700 dark:text-stone-300">
            {{ labels.review_application }}
        </label>
        <div class="relative">
            <select
                :id="`status-${application.id}`"
                v-model="form.status"
                name="status"
                class="min-h-11 w-full appearance-none rounded-lg border border-stone-300 bg-white py-2 pl-3 pr-10 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
            >
                <option value="" disabled>{{ labels.select_status }}</option>
                <option v-for="option in STATUS_OPTIONS" :key="option" :value="option">
                    {{ labels[option] }}
                </option>
            </select>
            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </div>
        <p v-if="form.errors.status" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.status }}</p>

        <div v-if="noteTemplates.length" data-note-template-picker class="space-y-1">
            <span class="block text-xs font-semibold text-stone-500 dark:text-stone-400">{{ labels.note_templates }}</span>
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="template in noteTemplates"
                    :key="template.id"
                    type="button"
                    class="min-h-9 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                    @click="form.notes = template.body"
                >
                    {{ template.name }}
                </button>
            </div>
        </div>

        <div class="flex justify-end">
            <ExpandedTextarea
                v-model="form.notes"
                :title="labels.expand_notes"
                :expand-label="labels.expand"
                :cancel-label="labels.cancel"
                :done-label="labels.done"
                :close-label="labels.close"
                :placeholder="labels.add_notes_placeholder"
            />
        </div>
        <textarea
            ref="notesTextarea"
            v-model="form.notes"
            name="notes"
            :placeholder="labels.add_notes_placeholder"
            class="min-h-24 w-full resize-y rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
            rows="4"
            @input="autosizeNotes"
        ></textarea>
        <p v-if="form.errors.notes" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.notes }}</p>

        <fieldset v-if="form.status === 'interview'" class="space-y-3 rounded-lg border border-violet-200 p-3 dark:border-violet-500/30">
            <legend class="text-sm font-semibold text-stone-700 dark:text-stone-300">{{ labels.interview_details }}</legend>
            <div class="space-y-2">
                <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.interview_mode }}</span>
                <div class="grid grid-cols-2 gap-2">
                    <label
                        class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm transition focus-within:ring-2 focus-within:ring-amber-400 dark:focus-within:ring-amber-500/40"
                        :class="form.interview_mode === 'onsite' ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-stone-300 dark:border-stone-600'"
                    >
                        <input v-model="form.interview_mode" type="radio" name="interview_mode" value="onsite" class="sr-only">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        {{ labels.interview_onsite }}
                    </label>
                    <label
                        class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm transition focus-within:ring-2 focus-within:ring-amber-400 dark:focus-within:ring-amber-500/40"
                        :class="form.interview_mode === 'online' ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-stone-300 dark:border-stone-600'"
                    >
                        <input v-model="form.interview_mode" type="radio" name="interview_mode" value="online" class="sr-only">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                        {{ labels.interview_online }}
                    </label>
                </div>
            </div>
            <div>
                <label :for="`interview_at-${application.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.interview_at }}</label>
                <input
                    :id="`interview_at-${application.id}`"
                    v-model="form.interview_at"
                    type="datetime-local"
                    name="interview_at"
                    class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
                >
                <p v-if="form.errors.interview_at" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.interview_at }}</p>
            </div>
            <div v-if="form.interview_mode === 'onsite'">
                <label :for="`interview_location-${application.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.interview_location }}</label>
                <input
                    :id="`interview_location-${application.id}`"
                    v-model="form.interview_location"
                    type="text"
                    name="interview_location"
                    class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
                >
                <p v-if="form.errors.interview_location" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.interview_location }}</p>
            </div>
            <div v-else>
                <label :for="`interview_url-${application.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.interview_url }}</label>
                <input
                    :id="`interview_url-${application.id}`"
                    v-model="form.interview_url"
                    type="url"
                    name="interview_url"
                    class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
                >
                <p v-if="form.errors.interview_url" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.interview_url }}</p>
            </div>
            <div>
                <div class="flex items-center justify-between gap-3">
                    <label :for="`interview_instructions-${application.id}`" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ labels.interview_instructions }}</label>
                    <ExpandedTextarea
                        v-model="form.interview_instructions"
                        :title="labels.expand_interview_instructions"
                        :expand-label="labels.expand"
                        :cancel-label="labels.cancel"
                        :done-label="labels.done"
                        :close-label="labels.close"
                    />
                </div>
                <textarea
                    :id="`interview_instructions-${application.id}`"
                    v-model="form.interview_instructions"
                    name="interview_instructions"
                    rows="2"
                    class="mt-1 w-full resize-y rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20"
                ></textarea>
                <p v-if="form.errors.interview_instructions" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.interview_instructions }}</p>
            </div>
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.interview_details_hint }}</p>
        </fieldset>

        <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 disabled:opacity-60 dark:focus:ring-offset-stone-900"
        >
            {{ labels.update }}
        </button>
    </form>
</template>