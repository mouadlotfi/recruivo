<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import type { JobFormData, PageProps, RecruiterJobDetail } from '../../types'

const props = defineProps<{
    mode: 'create' | 'edit'
    job: RecruiterJobDetail | null
    categories: string[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const localeUrl = (path: string) => `/${page.props.locale}${path}`

// Contract values validated server-side (in:remote,hybrid,onsite /
// in:draft,published) — labels come from the labels prop.
const REMOTE_TYPES = ['remote', 'hybrid', 'onsite'] as const
const STATUSES = ['draft', 'published'] as const

const emptyForm = (): JobFormData => ({
    title: '',
    description: '',
    location: '',
    category: '',
    remote_type: '',
    salary_min: '',
    salary_max: '',
    closes_at: '',
    status: 'draft',
})

const form = useForm<JobFormData>(
    props.mode === 'edit' && props.job
        ? {
            title: props.job.title,
            description: props.job.description,
            location: props.job.location ?? '',
            category: props.job.category ?? '',
            remote_type: props.job.remote_type ?? '',
            salary_min: props.job.salary_min === null ? '' : String(props.job.salary_min),
            salary_max: props.job.salary_max === null ? '' : String(props.job.salary_max),
            closes_at: props.job.closes_at ?? '',
            status: props.job.status,
        }
        : emptyForm(),
)

const actionUrl = computed(() =>
    props.mode === 'edit'
        ? localeUrl(`/recruiter/jobs/${props.job?.id}`)
        : localeUrl('/recruiter/jobs'),
)

// Local calendar date (matches the server's `today()` for the closes_at min).
const today = (() => {
    const now = new Date()
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
})()

const submit = () => {
    if (props.mode === 'edit') {
        form.patch(actionUrl.value)
    } else {
        form.post(actionUrl.value)
    }
}

const inputClass =
    'w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500'
const labelClass = 'text-sm font-medium text-stone-700 dark:text-stone-200'
const errorClass = 'mt-1 text-xs text-red-600 dark:text-red-400'
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div
            v-if="mode === 'edit' && job?.is_expired"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
        >
            {{ labels.expired_job_notice }}
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label for="title" :class="labelClass">{{ labels.job_title }}</label>
                <input
                    id="title"
                    v-model="form.title"
                    type="text"
                    required
                    :placeholder="labels.job_title_placeholder"
                    :class="inputClass"
                >
                <p v-if="form.errors.title" :class="errorClass">{{ form.errors.title }}</p>
            </div>

            <div class="space-y-2">
                <label for="location" :class="labelClass">{{ labels.location }}</label>
                <input
                    id="location"
                    v-model="form.location"
                    type="text"
                    required
                    :placeholder="labels.location_placeholder"
                    :class="inputClass"
                >
                <p v-if="form.errors.location" :class="errorClass">{{ form.errors.location }}</p>
            </div>
        </div>

        <div class="space-y-2">
            <label for="description" :class="labelClass">{{ labels.job_description }}</label>
            <textarea
                id="description"
                v-model="form.description"
                rows="6"
                required
                :placeholder="labels.description_placeholder"
                :class="inputClass"
            ></textarea>
            <p v-if="form.errors.description" :class="errorClass">{{ form.errors.description }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="space-y-2">
                <label for="category" :class="labelClass">{{ labels.category }}</label>
                <select id="category" v-model="form.category" :class="inputClass">
                    <option value="" disabled>{{ labels.select_category }}</option>
                    <option v-for="category in categories" :key="category" :value="category">
                        {{ labels[category.toLowerCase()] ?? category }}
                    </option>
                </select>
                <p v-if="form.errors.category" :class="errorClass">{{ form.errors.category }}</p>
            </div>

            <div class="space-y-2">
                <label for="remote_type" :class="labelClass">{{ labels.remote_type }}</label>
                <select id="remote_type" v-model="form.remote_type" :class="inputClass">
                    <option value="" disabled>{{ labels.select_remote_type }}</option>
                    <option v-for="type in REMOTE_TYPES" :key="type" :value="type">
                        {{ labels[type] ?? type }}
                    </option>
                </select>
                <p v-if="form.errors.remote_type" :class="errorClass">{{ form.errors.remote_type }}</p>
            </div>

            <div class="space-y-2">
                <label for="status" :class="labelClass">{{ labels.status }}</label>
                <select id="status" v-model="form.status" :class="inputClass">
                    <option v-for="status in STATUSES" :key="status" :value="status">
                        {{ labels[status] ?? status }}
                    </option>
                </select>
                <p v-if="form.errors.status" :class="errorClass">{{ form.errors.status }}</p>
            </div>
        </div>

        <div class="space-y-2">
            <label for="closes_at" :class="labelClass">{{ labels.closing_date }}</label>
            <input
                id="closes_at"
                v-model="form.closes_at"
                type="date"
                :min="today"
                :class="inputClass"
            >
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.closing_date_help }}</p>
            <p v-if="form.errors.closes_at" :class="errorClass">{{ form.errors.closes_at }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label for="salary_min" :class="labelClass">{{ labels.minimum_salary }}</label>
                <input
                    id="salary_min"
                    v-model="form.salary_min"
                    type="number"
                    min="0"
                    :placeholder="labels.salary_placeholder_min"
                    :class="inputClass"
                >
                <p v-if="form.errors.salary_min" :class="errorClass">{{ form.errors.salary_min }}</p>
            </div>

            <div class="space-y-2">
                <label for="salary_max" :class="labelClass">{{ labels.maximum_salary }}</label>
                <input
                    id="salary_max"
                    v-model="form.salary_max"
                    type="number"
                    :min="form.salary_min || 0"
                    :placeholder="labels.salary_placeholder_max"
                    :class="inputClass"
                >
                <p v-if="form.errors.salary_max" :class="errorClass">{{ form.errors.salary_max }}</p>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <Link
                :href="localeUrl('/recruiter/jobs')"
                class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-stone-100 px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
            >
                {{ labels.cancel }}
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                :aria-busy="form.processing"
                class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 disabled:opacity-60"
            >
                {{ form.processing ? labels.loading : (mode === 'create' ? labels.create_job : labels.update_job) }}
            </button>
        </div>
    </form>
</template>
