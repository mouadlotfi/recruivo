<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import type {
    CandidateProfileSettings,
    CompanyProfileSettings,
    PageProps,
    ProfileCompletion,
    ProfileEducation,
    ProfileExperience,
    ProfileLanguage,
    ProfileLink,
    ProfileUser,
} from '../../types'

interface ProfileFormData {
    name: string
    phone: string
    profile_summary: string
    headline: string
    skills: string
    languages_json: string
    links_json: string
    experiences_json: string
    educations_json: string
    preferred_categories: string[]
    resume: File | null
    company: {
        name: string
        tagline: string
        location: string
        website_url: string
        linkedin_url: string
        mission: string
        culture: string
    }
    logo: File | null
}

interface PasswordFormData {
    current_password: string
    password: string
    password_confirmation: string
}

interface EmailFormData {
    email: string
}

const props = defineProps<{
    user: ProfileUser
    candidateProfile: CandidateProfileSettings | null
    company: CompanyProfileSettings | null
    profileCompletion: ProfileCompletion | null
    categories: string[]
    languages: string[]
    linkTypes: string[]
    labels: Record<string, string>
}>()

const page = usePage<PageProps>()
const locale = computed(() => page.props.locale)
const localeUrl = (path: string, targetLocale = locale.value) => `/${targetLocale}${path}`
const isCandidate = computed(() => props.user.roles.includes('Candidate'))
const isRecruiter = computed(() => props.user.roles.includes('Recruiter'))

const inputClass = 'w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500'
const builderInputClass = 'w-full rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-800 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-950 dark:text-white'
const labelClass = 'text-sm font-medium text-stone-700 dark:text-stone-200'
const builderLabelClass = 'block text-sm font-medium text-stone-700 dark:text-stone-200'
const errorClass = 'mt-1 text-xs text-red-600 dark:text-red-400'
const panelClass = 'rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60'
const currentYear = new Date().getFullYear()
const experienceYears = Array.from({ length: 71 }, (_, index) => currentYear - index)
const educationEndYears = Array.from({ length: 76 }, (_, index) => currentYear + 5 - index)
const monthLabel = computed(() => props.labels.month ?? (locale.value === 'fr' ? 'Mois' : 'Month'))
const yearLabel = computed(() => props.labels.year ?? (locale.value === 'fr' ? 'Année' : 'Year'))
const months = computed(() => Array.from({ length: 12 }, (_, index) => ({
    value: String(index + 1).padStart(2, '0'),
    label: new Intl.DateTimeFormat(locale.value, { month: 'long', timeZone: 'UTC' }).format(new Date(Date.UTC(2000, index, 1))),
})))

const languages = ref<ProfileLanguage[]>(props.candidateProfile?.languages_data?.map((item) => ({
    language: item.language,
    proficiency: item.proficiency,
})) ?? [])
const links = ref<ProfileLink[]>(props.candidateProfile?.profile_links?.map((item) => ({
    name: item.name,
    url: item.url,
})) ?? [])
const experiences = ref<ProfileExperience[]>(props.candidateProfile?.experiences?.map((item) => ({
    job_title: item.job_title ?? '',
    company_name: item.company_name ?? '',
    location: item.location ?? '',
    start_date: normalizeDate(item.start_date),
    end_date: normalizeDate(item.end_date),
    is_current: Boolean(item.is_current),
    description: item.description ?? '',
})) ?? [])
const educations = ref<ProfileEducation[]>(props.candidateProfile?.educations?.map((item) => ({
    school: item.school ?? '',
    degree: item.degree ?? '',
    field_of_study: item.field_of_study ?? '',
    start_date: normalizeDate(item.start_date),
    end_date: normalizeDate(item.end_date),
    is_current: Boolean(item.is_current),
    description: item.description ?? '',
})) ?? [])

function normalizeDate(value: string | null | undefined): string {
    return value && /^\d{4}$/.test(value) ? `${value}-01` : value ?? ''
}

function datePart(value: string | null | undefined, part: 'month' | 'year'): string {
    const [year, month] = (value ?? '').split('-')
    return part === 'month' ? month ?? '' : year ?? ''
}

function eventValue(event: Event): string {
    return (event.target as HTMLSelectElement).value
}

function setDatePart(draft: ProfileExperience | ProfileEducation, field: 'start_date' | 'end_date', part: 'month' | 'year', value: string): void {
    const existing = draft[field] ?? ''
    const year = part === 'year' ? value : datePart(existing, 'year')
    const month = part === 'month' ? value : datePart(existing, 'month')
    draft[field] = year && month ? `${year}-${month}` : ''
}

function emptyProfileForm(): ProfileFormData {
    return {
        name: props.user.name,
        phone: props.user.phone ?? '',
        profile_summary: props.user.profile_summary ?? '',
        headline: props.candidateProfile?.headline ?? '',
        skills: props.candidateProfile?.skills ?? '',
        languages_json: '',
        links_json: '',
        experiences_json: '',
        educations_json: '',
        preferred_categories: [...(props.candidateProfile?.preferred_categories ?? [])],
        resume: null,
        company: {
            name: props.company?.name ?? '',
            tagline: props.company?.tagline ?? '',
            location: props.company?.location ?? '',
            website_url: props.company?.website_url ?? '',
            linkedin_url: props.company?.linkedin_url ?? '',
            mission: props.company?.mission ?? '',
            culture: props.company?.culture ?? '',
        },
        logo: null,
    }
}

const profileForm = useForm<ProfileFormData>(emptyProfileForm())
const passwordForm = useForm<PasswordFormData>({
    current_password: '',
    password: '',
    password_confirmation: '',
})
const emailForm = useForm<EmailFormData>({ email: '' })
const showDeleteModal = ref(false)

const editingLanguage = ref<number | null>(null)
const languageError = ref('')
const languageDraft = reactive<ProfileLanguage>({ language: '', proficiency: 'intermediate' })
const editingLink = ref<number | null>(null)
const linkError = ref('')
const linkDraft = reactive<ProfileLink>({ name: '', url: '' })
const editingExperience = ref<number | null>(null)
const experienceError = ref('')
const experienceDraft = reactive<ProfileExperience>({
    job_title: '',
    company_name: '',
    location: '',
    start_date: '',
    end_date: '',
    is_current: false,
    description: '',
})
const editingEducation = ref<number | null>(null)
const educationError = ref('')
const educationDraft = reactive<ProfileEducation>({
    school: '',
    degree: '',
    field_of_study: '',
    start_date: '',
    end_date: '',
    is_current: false,
    description: '',
})

const proficiencyLevels = [
    'beginner',
    'elementary',
    'intermediate',
    'professional_working',
    'fluent',
    'native_bilingual',
]

function resetLanguageDraft(): void {
    Object.assign(languageDraft, { language: '', proficiency: 'intermediate' })
    editingLanguage.value = null
    languageError.value = ''
}

function editLanguage(index: number): void {
    Object.assign(languageDraft, languages.value[index])
    editingLanguage.value = index
    languageError.value = ''
}

function saveLanguage(): void {
    if (!languageDraft.language || !languageDraft.proficiency) {
        languageError.value = props.labels.complete_required_fields
        return
    }

    if (editingLanguage.value === null || editingLanguage.value < 0) {
        languages.value.push({ ...languageDraft })
    } else {
        languages.value[editingLanguage.value] = { ...languageDraft }
    }
    resetLanguageDraft()
}

function removeLanguage(index: number): void {
    languages.value.splice(index, 1)
}

function resetLinkDraft(): void {
    Object.assign(linkDraft, { name: '', url: '' })
    editingLink.value = null
    linkError.value = ''
}

function editLink(index: number): void {
    Object.assign(linkDraft, links.value[index])
    editingLink.value = index
    linkError.value = ''
}

function saveLink(): void {
    if (!linkDraft.name || !linkDraft.url) {
        linkError.value = props.labels.complete_required_fields
        return
    }

    const duplicate = links.value.some((item, index) => item.name === linkDraft.name && index !== editingLink.value)
    if (duplicate) {
        linkError.value = props.labels.link_type_unique
        return
    }
    if (editingLink.value === null && links.value.length >= 5) {
        linkError.value = props.labels.links_max
        return
    }

    if (editingLink.value === null || editingLink.value < 0) {
        links.value.push({ ...linkDraft })
    } else {
        links.value[editingLink.value] = { ...linkDraft }
    }
    resetLinkDraft()
}

function removeLink(index: number): void {
    links.value.splice(index, 1)
}

function resetExperienceDraft(): void {
    Object.assign(experienceDraft, {
        job_title: '',
        company_name: '',
        location: '',
        start_date: '',
        end_date: '',
        is_current: false,
        description: '',
    })
    editingExperience.value = null
    experienceError.value = ''
}

function editExperience(index: number): void {
    Object.assign(experienceDraft, experiences.value[index])
    editingExperience.value = index
    experienceError.value = ''
}

function saveExperience(): void {
    if (!experienceDraft.job_title || !experienceDraft.company_name || !experienceDraft.start_date || (!experienceDraft.is_current && !experienceDraft.end_date)) {
        experienceError.value = props.labels.complete_required_fields
        return
    }
    if (!experienceDraft.is_current && experienceDraft.end_date && experienceDraft.end_date < experienceDraft.start_date) {
        experienceError.value = props.labels.end_date_after_start
        return
    }

    const item = { ...experienceDraft, end_date: experienceDraft.is_current ? null : experienceDraft.end_date }
    if (editingExperience.value === null || editingExperience.value < 0) {
        experiences.value.push(item)
    } else {
        experiences.value[editingExperience.value] = item
    }
    resetExperienceDraft()
}

function removeExperience(index: number): void {
    experiences.value.splice(index, 1)
}

function resetEducationDraft(): void {
    Object.assign(educationDraft, {
        school: '',
        degree: '',
        field_of_study: '',
        start_date: '',
        end_date: '',
        is_current: false,
        description: '',
    })
    editingEducation.value = null
    educationError.value = ''
}

function editEducation(index: number): void {
    Object.assign(educationDraft, educations.value[index])
    editingEducation.value = index
    educationError.value = ''
}

function saveEducation(): void {
    if (!educationDraft.school || !educationDraft.degree || !educationDraft.field_of_study || !educationDraft.start_date || (!educationDraft.is_current && !educationDraft.end_date)) {
        educationError.value = props.labels.complete_required_fields
        return
    }
    if (!educationDraft.is_current && educationDraft.end_date && educationDraft.end_date < educationDraft.start_date) {
        educationError.value = props.labels.end_date_after_start
        return
    }

    const item = { ...educationDraft, end_date: educationDraft.is_current ? null : educationDraft.end_date }
    if (editingEducation.value === null || editingEducation.value < 0) {
        educations.value.push(item)
    } else {
        educations.value[editingEducation.value] = item
    }
    resetEducationDraft()
}

function removeEducation(index: number): void {
    educations.value.splice(index, 1)
}

function formatMonthYear(value: string | null): string {
    if (!value) {
        return props.labels.present
    }
    if (/^\d{4}$/.test(value)) {
        return value
    }
    const [year, month] = value.split('-')
    return new Intl.DateTimeFormat(locale.value, { month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(Number(year), Number(month) - 1, 1)))
}

function dateRange(start: string, end: string | null, current: boolean): string {
    return `${formatMonthYear(start)} – ${current ? props.labels.present : formatMonthYear(end)}`
}

function fieldError(key: string): string | undefined {
    return profileForm.errors[key]
}

function submitProfile(): void {
    profileForm.transform((data) => ({
        ...data,
        languages_json: JSON.stringify(languages.value),
        links_json: JSON.stringify(links.value),
        experiences_json: JSON.stringify(experiences.value),
        educations_json: JSON.stringify(educations.value),
        preferred_categories: data.preferred_categories.length > 0 ? data.preferred_categories : [''],
    })).put(localeUrl('/profile'), {
        forceFormData: true,
    })
}

function setResume(event: Event): void {
    const input = event.target as HTMLInputElement
    profileForm.resume = input.files?.[0] ?? null
}

function setLogo(event: Event): void {
    const input = event.target as HTMLInputElement
    profileForm.logo = input.files?.[0] ?? null
}

function submitPassword(): void {
    passwordForm.put(localeUrl('/profile/password'), {
        onSuccess: () => passwordForm.reset(),
    })
}

function submitEmail(): void {
    emailForm.put(localeUrl('/profile/email/request'), {
        onSuccess: () => emailForm.reset(),
    })
}

function deleteAccount(): void {
    router.delete(localeUrl('/profile'))
}
</script>

<template>
    <AppLayout>
        <Head :title="isRecruiter ? labels.company_profile : labels.profile_settings" />

        <div class="mx-auto max-w-4xl">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">
                    {{ isRecruiter ? labels.company_profile : labels.profile_settings }}
                </h1>
                <Link
                    v-if="isCandidate"
                    :href="localeUrl('/candidate/profile-preview')"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:border-amber-500/40 dark:text-amber-300 dark:hover:bg-amber-500/10"
                >
                    {{ labels.preview_as_recruiter }}
                </Link>
            </div>

            <div
                v-if="user.is_demo"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/30"
            >
                <h2 class="font-semibold text-amber-900 dark:text-amber-200">{{ labels.demo_read_only }}</h2>
                <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">{{ labels.demo_read_only_description }}</p>
            </div>

            <form class="space-y-6" @submit.prevent="submitProfile">
                <fieldset :disabled="user.is_demo" class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">
                    <section :class="[panelClass, 'mb-6']">
                        <h2 class="mb-6 text-xl font-semibold text-stone-900 dark:text-white">{{ labels.profile_information }}</h2>

                        <div v-if="isCandidate && profileCompletion && profileCompletion.percentage < 100" class="mb-6 rounded-xl border border-amber-200/60 bg-amber-50/60 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-stone-900 dark:text-white">{{ labels.profile_completion }}</h3>
                                    <p class="text-xs text-stone-600 dark:text-stone-400">{{ labels.profile_completion_help }}</p>
                                </div>
                                <span class="text-lg font-bold text-stone-900 dark:text-white">{{ profileCompletion.percentage }}%</span>
                            </div>
                            <div class="mt-2" role="progressbar" :aria-valuenow="profileCompletion.percentage" aria-valuemin="0" aria-valuemax="100" :aria-label="labels.profile_completion">
                                <div class="h-2 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-700">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all duration-500" :style="{ width: `${profileCompletion.percentage}%` }"></div>
                                </div>
                            </div>
                            <p v-if="profileCompletion.missing.length" class="mt-2 text-xs text-stone-600 dark:text-stone-400">{{ profileCompletion.steps_label }}</p>
                            <p v-else class="mt-2 text-xs font-medium text-green-700 dark:text-green-400">{{ labels.profile_complete }}</p>
                        </div>

                        <div v-if="isCandidate" class="space-y-6">
                            <div class="space-y-2">
                                <label for="profile_name" :class="labelClass">{{ labels.full_name }}</label>
                                <input id="profile_name" v-model="profileForm.name" type="text" required :class="inputClass">
                                <p v-if="fieldError('name')" :class="errorClass" role="alert">{{ fieldError('name') }}</p>
                            </div>
                            <div class="space-y-2">
                                <label for="profile_phone" :class="labelClass">{{ labels.phone_number }}</label>
                                <input id="profile_phone" v-model="profileForm.phone" type="tel" :placeholder="labels.phone_placeholder" :class="inputClass">
                                <p v-if="fieldError('phone')" :class="errorClass" role="alert">{{ fieldError('phone') }}</p>
                            </div>
                            <div class="space-y-2">
                                <label for="profile_summary" :class="labelClass">{{ labels.about }}</label>
                                <textarea id="profile_summary" v-model="profileForm.profile_summary" rows="5" maxlength="1000" :placeholder="labels.about_placeholder" :class="inputClass"></textarea>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.about_help }}</p>
                                <p v-if="fieldError('profile_summary')" :class="errorClass" role="alert">{{ fieldError('profile_summary') }}</p>
                            </div>
                            <div class="space-y-2">
                                <label for="profile_headline" :class="labelClass">{{ labels.professional_headline }}</label>
                                <input id="profile_headline" v-model="profileForm.headline" type="text" :placeholder="labels.headline_placeholder" :class="inputClass">
                                <p v-if="fieldError('headline')" :class="errorClass" role="alert">{{ fieldError('headline') }}</p>
                            </div>
                            <div class="space-y-2">
                                <label for="profile_skills" :class="labelClass">{{ labels.skills }}</label>
                                <textarea id="profile_skills" v-model="profileForm.skills" rows="3" :placeholder="labels.skills_placeholder" :class="inputClass"></textarea>
                                <p v-if="fieldError('skills')" :class="errorClass" role="alert">{{ fieldError('skills') }}</p>
                            </div>
                        </div>

                        <div v-else-if="isRecruiter && company" class="space-y-6">
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label for="company_name" :class="labelClass">{{ labels.company_name }}</label>
                                    <input id="company_name" v-model="profileForm.company.name" type="text" required :class="inputClass">
                                    <p v-if="fieldError('company.name')" :class="errorClass" role="alert">{{ fieldError('company.name') }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label for="company_tagline" :class="labelClass">{{ labels.tagline }}</label>
                                    <input id="company_tagline" v-model="profileForm.company.tagline" type="text" :class="inputClass">
                                </div>
                                <div class="space-y-2">
                                    <label for="company_location" :class="labelClass">{{ labels.location }}</label>
                                    <input id="company_location" v-model="profileForm.company.location" type="text" :class="inputClass">
                                </div>
                                <div class="space-y-2">
                                    <label for="company_website_url" :class="labelClass">{{ labels.website_url }}</label>
                                    <input id="company_website_url" v-model="profileForm.company.website_url" type="url" :placeholder="labels.website_placeholder" :class="inputClass">
                                </div>
                                <div class="space-y-2">
                                    <label for="company_linkedin_url" :class="labelClass">{{ labels.linkedin_url }}</label>
                                    <input id="company_linkedin_url" v-model="profileForm.company.linkedin_url" type="url" :placeholder="labels.linkedin_placeholder" :class="inputClass">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="company_mission" :class="labelClass">{{ labels.mission }}</label>
                                <textarea id="company_mission" v-model="profileForm.company.mission" rows="3" :class="inputClass"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label for="company_culture" :class="labelClass">{{ labels.culture }}</label>
                                <textarea id="company_culture" v-model="profileForm.company.culture" rows="3" :class="inputClass"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label for="logo" :class="labelClass">{{ labels.company_logo }}</label>
                                <img v-if="company.logo_url" :src="company.logo_url" alt="Company logo" class="mb-2 h-20 w-20 rounded-lg object-cover">
                                <div class="flex items-center gap-3">
                                    <label for="logo" class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                        {{ labels.choose_logo }}
                                    </label>
                                    <input id="logo" type="file" accept="image/*" class="hidden" @change="setLogo">
                                    <span class="text-sm text-stone-600 dark:text-stone-400">{{ profileForm.logo?.name ?? labels.no_file_chosen }}</span>
                                </div>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.logo_formats }}</p>
                                <p v-if="fieldError('logo')" :class="errorClass" role="alert">{{ fieldError('logo') }}</p>
                            </div>
                        </div>

                        <div v-if="isCandidate" class="space-y-6">
                            <section class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div><h3 class="font-semibold text-stone-900 dark:text-white">{{ labels.languages }}</h3><p class="mt-1 text-sm text-stone-500">{{ labels.languages_help }}</p></div>
                                    <button type="button" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 dark:border-amber-500/40 dark:text-amber-300" @click="editingLanguage = -1; languageError = ''">{{ labels.add_language }}</button>
                                </div>
                                <div v-if="languages.length" class="mt-4 space-y-3">
                                    <article v-for="(item, index) in languages" :key="`${item.language}-${index}`" class="flex flex-col gap-3 rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70 sm:flex-row sm:items-center sm:justify-between">
                                        <div><p class="font-medium text-stone-900 dark:text-white">{{ item.language }}</p><p class="text-sm text-stone-500">{{ labels[`proficiency_${item.proficiency}`] ?? item.proficiency }}</p></div>
                                        <div class="flex gap-2"><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300" @click="editLanguage(index)">{{ labels.edit_entry }}</button><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400" @click="removeLanguage(index)">{{ labels.remove_entry }}</button></div>
                                    </article>
                                </div>
                                <p v-else-if="editingLanguage === null" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ labels.no_languages }}</p>
                                <div v-if="editingLanguage !== null" class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
                                    <div><label for="language_name" :class="builderLabelClass">{{ labels.language_name }}</label><select id="language_name" v-model="languageDraft.language" :class="[builderInputClass, 'mt-1']"><option value="">{{ labels.select_language }}</option><option v-for="language in props.languages" :key="language" :value="language">{{ language }}</option></select></div>
                                    <div><label for="proficiency_level" :class="builderLabelClass">{{ labels.proficiency_level }}</label><select id="proficiency_level" v-model="languageDraft.proficiency" :class="[builderInputClass, 'mt-1']"><option v-for="level in proficiencyLevels" :key="level" :value="level">{{ labels[`proficiency_${level}`] }}</option></select></div>
                                    <p v-if="languageError" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400" role="alert">{{ languageError }}</p>
                                    <div class="flex gap-2 sm:col-span-2"><button type="button" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white" @click="saveLanguage">{{ labels.save_entry }}</button><button type="button" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300" @click="resetLanguageDraft">{{ labels.cancel }}</button></div>
                                </div>
                            </section>

                            <section class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div><h3 class="font-semibold text-stone-900 dark:text-white">{{ labels.links }}</h3><p class="mt-1 text-sm text-stone-500">{{ labels.links_help }}</p></div>
                                    <button type="button" :disabled="links.length >= 5" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-amber-500/40 dark:text-amber-300" @click="editingLink = -1; linkError = ''">{{ labels.add_link }}</button>
                                </div>
                                <div v-if="links.length" class="mt-4 space-y-3">
                                    <article v-for="(item, index) in links" :key="`${item.name}-${index}`" class="flex flex-col gap-3 rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="font-medium text-stone-900 dark:text-white">{{ item.name === 'Personal Website' ? labels.personal_website : item.name }}</p><p class="truncate text-sm text-stone-500">{{ item.url }}</p></div><div class="flex gap-2"><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300" @click="editLink(index)">{{ labels.edit_entry }}</button><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400" @click="removeLink(index)">{{ labels.remove_entry }}</button></div></article>
                                </div>
                                <p v-else-if="editingLink === null" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ labels.no_links }}</p>
                                <div v-if="editingLink !== null" class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
                                    <div><label for="link_name" :class="builderLabelClass">{{ labels.link_name }}</label><select id="link_name" v-model="linkDraft.name" :class="[builderInputClass, 'mt-1']"><option value="">{{ labels.select_link_type }}</option><option v-for="type in props.linkTypes" :key="type" :value="type" :disabled="links.some((item, index) => item.name === type && index !== editingLink)">{{ type === 'Personal Website' ? labels.personal_website : type }}</option></select></div>
                                    <div><label for="link_url" :class="builderLabelClass">{{ labels.link_url }}</label><input id="link_url" v-model.trim="linkDraft.url" type="url" inputmode="url" maxlength="500" placeholder="https://" :class="[builderInputClass, 'mt-1']"></div>
                                    <p v-if="linkError" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400" role="alert">{{ linkError }}</p>
                                    <div class="flex gap-2 sm:col-span-2"><button type="button" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white" @click="saveLink">{{ labels.save_entry }}</button><button type="button" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300" @click="resetLinkDraft">{{ labels.cancel }}</button></div>
                                </div>
                                <p class="mt-3 text-xs text-stone-500">{{ links.length }}/5 {{ labels.links_used }}</p>
                            </section>

                            <section class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
                                <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-stone-900 dark:text-white">{{ labels.experience }}</h3><p class="mt-1 text-sm text-stone-500">{{ labels.experience_help }}</p></div><button type="button" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 dark:border-amber-500/40 dark:text-amber-300" @click="editingExperience = -1; experienceError = ''">{{ labels.add_experience }}</button></div>
                                <div v-if="experiences.length" class="mt-4 space-y-3"><article v-for="(item, index) in experiences" :key="`${item.job_title}-${index}`" class="rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70"><div class="flex flex-col gap-3 sm:flex-row sm:justify-between"><div><h4 class="font-semibold text-stone-900 dark:text-white">{{ item.job_title }}</h4><p class="text-sm text-stone-700 dark:text-stone-300">{{ item.company_name }}<span v-if="item.location"> · {{ item.location }}</span></p><p class="mt-1 text-sm text-stone-500">{{ dateRange(item.start_date, item.end_date, item.is_current) }}</p></div><div class="flex gap-2"><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300" @click="editExperience(index)">{{ labels.edit_entry }}</button><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400" @click="removeExperience(index)">{{ labels.remove_entry }}</button></div></div><p v-if="item.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ item.description }}</p></article></div>
                                <p v-else-if="editingExperience === null" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ labels.no_experience }}</p>
                                <div v-if="editingExperience !== null" class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
                                    <div><label for="experience_job_title" :class="builderLabelClass">{{ labels.job_title }}</label><input id="experience_job_title" v-model.trim="experienceDraft.job_title" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div><div><label for="experience_company_name" :class="builderLabelClass">{{ labels.company_name_entry }}</label><input id="experience_company_name" v-model.trim="experienceDraft.company_name" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div>
                                    <div class="sm:col-span-2"><label for="experience_location" :class="builderLabelClass">{{ labels.location }}</label><input id="experience_location" v-model.trim="experienceDraft.location" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div>
                                    <div><label for="experience_start_date_month" :class="builderLabelClass">{{ labels.start_date }}</label><div class="mt-1 grid grid-cols-2 gap-2"><select id="experience_start_date_month" :value="datePart(experienceDraft.start_date, 'month')" :class="builderInputClass" @change="setDatePart(experienceDraft, 'start_date', 'month', eventValue($event))"><option value="">{{ monthLabel }}</option><option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option></select><select id="experience_start_date_year" :value="datePart(experienceDraft.start_date, 'year')" :class="builderInputClass" @change="setDatePart(experienceDraft, 'start_date', 'year', eventValue($event))"><option value="">{{ yearLabel }}</option><option v-for="year in experienceYears" :key="year" :value="year">{{ year }}</option></select></div></div><div><label for="experience_end_date_month" :class="builderLabelClass">{{ labels.end_date }}</label><div class="mt-1 grid grid-cols-2 gap-2"><select id="experience_end_date_month" :value="datePart(experienceDraft.end_date, 'month')" :disabled="experienceDraft.is_current" :class="builderInputClass" @change="setDatePart(experienceDraft, 'end_date', 'month', eventValue($event))"><option value="">{{ monthLabel }}</option><option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option></select><select id="experience_end_date_year" :value="datePart(experienceDraft.end_date, 'year')" :disabled="experienceDraft.is_current" :class="builderInputClass" @change="setDatePart(experienceDraft, 'end_date', 'year', eventValue($event))"><option value="">{{ yearLabel }}</option><option v-for="year in experienceYears" :key="year" :value="year">{{ year }}</option></select></div></div>
                                    <label class="flex min-h-11 items-center gap-3 sm:col-span-2"><input v-model="experienceDraft.is_current" type="checkbox" class="h-5 w-5 rounded border-stone-300 text-amber-600 focus:ring-amber-500" @change="experienceDraft.is_current && (experienceDraft.end_date = null)"><span class="text-sm text-stone-700 dark:text-stone-300">{{ labels.currently_work_here }}</span></label>
                                    <div class="sm:col-span-2"><label for="experience_description" :class="builderLabelClass">{{ labels.description_responsibilities }}</label><textarea id="experience_description" v-model.trim="experienceDraft.description" rows="4" maxlength="3000" :class="[builderInputClass, 'mt-1']"></textarea></div>
                                    <p v-if="experienceError" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400" role="alert">{{ experienceError }}</p><div class="flex gap-2 sm:col-span-2"><button type="button" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white" @click="saveExperience">{{ labels.save_entry }}</button><button type="button" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300" @click="resetExperienceDraft">{{ labels.cancel }}</button></div>
                                </div>
                            </section>

                            <section class="rounded-2xl border border-stone-200/80 p-5 dark:border-stone-700">
                                <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold text-stone-900 dark:text-white">{{ labels.education }}</h3><p class="mt-1 text-sm text-stone-500">{{ labels.education_help }}</p></div><button type="button" class="min-h-11 rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 dark:border-amber-500/40 dark:text-amber-300" @click="editingEducation = -1; educationError = ''">{{ labels.add_education }}</button></div>
                                <div v-if="educations.length" class="mt-4 space-y-3"><article v-for="(item, index) in educations" :key="`${item.school}-${index}`" class="rounded-xl bg-stone-50 p-4 dark:bg-stone-800/70"><div class="flex flex-col gap-3 sm:flex-row sm:justify-between"><div><h4 class="font-semibold text-stone-900 dark:text-white">{{ item.school }}</h4><p class="text-sm text-stone-700 dark:text-stone-300">{{ item.degree }} · {{ item.field_of_study }}</p><p class="mt-1 text-sm text-stone-500">{{ dateRange(item.start_date, item.end_date, item.is_current) }}</p></div><div class="flex gap-2"><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-amber-700 dark:text-amber-300" @click="editEducation(index)">{{ labels.edit_entry }}</button><button type="button" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-red-600 dark:text-red-400" @click="removeEducation(index)">{{ labels.remove_entry }}</button></div></div><p v-if="item.description" class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ item.description }}</p></article></div>
                                <p v-else-if="editingEducation === null" class="mt-4 rounded-xl bg-stone-50 p-4 text-sm text-stone-500 dark:bg-stone-800/70">{{ labels.no_education }}</p>
                                <div v-if="editingEducation !== null" class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-500/30 dark:bg-amber-500/5 sm:grid-cols-2">
                                    <div class="sm:col-span-2"><label for="education_school" :class="builderLabelClass">{{ labels.school }}</label><input id="education_school" v-model.trim="educationDraft.school" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div><div><label for="education_degree" :class="builderLabelClass">{{ labels.degree }}</label><input id="education_degree" v-model.trim="educationDraft.degree" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div><div><label for="education_field" :class="builderLabelClass">{{ labels.field_of_study }}</label><input id="education_field" v-model.trim="educationDraft.field_of_study" type="text" maxlength="150" :class="[builderInputClass, 'mt-1']"></div>
                                    <div><label for="education_start_date_month" :class="builderLabelClass">{{ labels.start_date }}</label><div class="mt-1 grid grid-cols-2 gap-2"><select id="education_start_date_month" :value="datePart(educationDraft.start_date, 'month')" :class="builderInputClass" @change="setDatePart(educationDraft, 'start_date', 'month', eventValue($event))"><option value="">{{ monthLabel }}</option><option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option></select><select id="education_start_date_year" :value="datePart(educationDraft.start_date, 'year')" :class="builderInputClass" @change="setDatePart(educationDraft, 'start_date', 'year', eventValue($event))"><option value="">{{ yearLabel }}</option><option v-for="year in experienceYears" :key="year" :value="year">{{ year }}</option></select></div></div><div><label for="education_end_date_month" :class="builderLabelClass">{{ labels.end_date }}</label><div class="mt-1 grid grid-cols-2 gap-2"><select id="education_end_date_month" :value="datePart(educationDraft.end_date, 'month')" :disabled="educationDraft.is_current" :class="builderInputClass" @change="setDatePart(educationDraft, 'end_date', 'month', eventValue($event))"><option value="">{{ monthLabel }}</option><option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option></select><select id="education_end_date_year" :value="datePart(educationDraft.end_date, 'year')" :disabled="educationDraft.is_current" :class="builderInputClass" @change="setDatePart(educationDraft, 'end_date', 'year', eventValue($event))"><option value="">{{ yearLabel }}</option><option v-for="year in educationEndYears" :key="year" :value="year">{{ year }}</option></select></div></div>
                                    <label class="flex min-h-11 items-center gap-3 sm:col-span-2"><input v-model="educationDraft.is_current" type="checkbox" class="h-5 w-5 rounded border-stone-300 text-amber-600 focus:ring-amber-500" @change="educationDraft.is_current && (educationDraft.end_date = null)"><span class="text-sm text-stone-700 dark:text-stone-300">{{ labels.currently_studying_here }}</span></label>
                                    <div class="sm:col-span-2"><label for="education_description" :class="builderLabelClass">{{ labels.additional_information }} <span class="font-normal text-stone-500">({{ labels.optional }})</span></label><textarea id="education_description" v-model.trim="educationDraft.description" rows="4" maxlength="3000" :class="[builderInputClass, 'mt-1']"></textarea><p class="mt-1 text-xs text-stone-500">{{ labels.education_description_optional_help }}</p></div>
                                    <p v-if="educationError" class="text-sm font-medium text-red-600 sm:col-span-2 dark:text-red-400" role="alert">{{ educationError }}</p><div class="flex gap-2 sm:col-span-2"><button type="button" class="min-h-11 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white" @click="saveEducation">{{ labels.save_entry }}</button><button type="button" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-stone-600 dark:text-stone-300" @click="resetEducationDraft">{{ labels.cancel }}</button></div>
                                </div>
                            </section>

                            <section class="space-y-2">
                                <h3 class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ labels.job_interests }}</h3>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.job_interests_help }}</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <label v-for="category in categories" :key="category" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-stone-200 bg-white/60 px-3 py-2 text-sm text-stone-700 transition hover:border-amber-300 dark:border-stone-700 dark:bg-stone-900/60 dark:text-stone-200">
                                        <input v-model="profileForm.preferred_categories" type="checkbox" :value="category" class="h-5 w-5 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                                        {{ category }}
                                    </label>
                                </div>
                                <p v-if="fieldError('preferred_categories')" :class="errorClass" role="alert">{{ fieldError('preferred_categories') }}</p>
                            </section>

                            <section class="space-y-2">
                                <label for="profile_resume" :class="labelClass">{{ labels.resume }}</label>
                                <div v-if="candidateProfile?.resume_path" class="mb-3 flex flex-col gap-4 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-500/25 dark:bg-emerald-500/10 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </span>
                                        <div class="min-w-0 pt-0.5"><p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ labels.resume_uploaded }}</p><p class="mt-1 text-xs leading-5 text-emerald-700 dark:text-emerald-300/80">{{ labels.resume_uploaded_help }}</p></div>
                                    </div>
                                    <a v-if="candidateProfile.resume_url" :href="candidateProfile.resume_url" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        {{ labels.view_resume }}
                                    </a>
                                </div>
                                <div class="flex flex-col gap-3 rounded-xl border border-dashed border-stone-300 bg-stone-50/70 p-4 dark:border-stone-700 dark:bg-stone-950/40 sm:flex-row sm:items-center">
                                    <label for="profile_resume" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-offset-stone-900">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 0L7.5 9M12 4.5 16.5 9M4.5 15.75v2.625A2.625 2.625 0 007.125 21h9.75a2.625 2.625 0 002.625-2.625V15.75" /></svg>
                                        {{ candidateProfile?.resume_path ? labels.replace_resume : labels.choose_file }}
                                    </label>
                                    <input id="profile_resume" type="file" accept=".pdf,.doc,.docx" class="peer sr-only" @change="setResume">
                                    <span class="min-w-0 flex-1 truncate text-sm text-stone-600 dark:text-stone-400" aria-live="polite">{{ profileForm.resume?.name ?? labels.no_file_chosen }}</span>
                                </div>
                                <p class="flex items-center gap-1.5 text-xs text-stone-500 dark:text-stone-400"><svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>{{ labels.resume_formats }}</p>
                                <p v-if="fieldError('resume')" :class="errorClass" role="alert">{{ fieldError('resume') }}</p>
                            </section>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" :disabled="profileForm.processing" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 disabled:opacity-60">
                                {{ profileForm.processing ? labels.save_entry : labels.update_profile }}
                            </button>
                        </div>
                    </section>
                </fieldset>
            </form>

            <section :class="[panelClass, 'mb-6']">
                <h2 class="mb-6 text-xl font-semibold text-stone-900 dark:text-white">{{ labels.language }}</h2>
                <p class="mb-4 text-sm text-stone-600 dark:text-stone-400">{{ labels.language_help }}</p>
                <div class="flex flex-wrap gap-3"><Link v-for="targetLocale in ['en', 'fr']" :key="targetLocale" :href="localeUrl('/profile', targetLocale)" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" :class="locale === targetLocale ? 'border-amber-400 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' : 'border-stone-200 text-stone-700 hover:border-amber-300 dark:border-stone-700 dark:text-stone-200 dark:hover:border-amber-500/40'"><span class="text-lg">{{ targetLocale === 'en' ? '🇬🇧' : '🇫🇷' }}</span>{{ targetLocale === 'en' ? labels.language_en : labels.language_fr }}</Link></div>
            </section>

            <section :class="[panelClass, 'mb-6']">
                <h2 class="mb-6 text-xl font-semibold text-stone-900 dark:text-white">{{ labels.change_email_address }}</h2>
                <div v-if="user.pending_email" class="mb-6 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200"><p class="font-medium">{{ labels.pending_email_change.replace(':email', user.pending_email) }}</p><p class="mt-1">{{ labels.check_new_email_inbox }}</p></div>
                <form class="space-y-6" @submit.prevent="submitEmail">
                    <fieldset :disabled="user.is_demo" class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">
                        <div class="space-y-2"><label for="current_email" :class="labelClass">{{ labels.current_email }}</label><input id="current_email" type="email" :value="user.email" disabled class="w-full rounded-2xl border border-stone-200/80 bg-stone-100 px-4 py-3 text-sm text-stone-500 shadow-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400"></div>
                        <div class="space-y-2"><label for="new_email" :class="labelClass">{{ labels.new_email_address }}</label><input id="new_email" v-model="emailForm.email" type="email" required :class="inputClass"><p class="text-xs text-stone-500 dark:text-stone-400">{{ labels.verification_email_sent }}</p><p v-if="emailForm.errors.email" :class="errorClass" role="alert">{{ emailForm.errors.email }}</p></div>
                        <div class="flex justify-end"><button type="submit" :disabled="emailForm.processing" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 disabled:opacity-60">{{ labels.request_email_change }}</button></div>
                    </fieldset>
                </form>
            </section>

            <section :class="[panelClass, 'mb-6']">
                <h2 class="mb-6 text-xl font-semibold text-stone-900 dark:text-white">{{ labels.change_password }}</h2>
                <form class="space-y-6" @submit.prevent="submitPassword">
                    <fieldset :disabled="user.is_demo" class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">
                        <div class="space-y-2"><label for="current_password" :class="labelClass">{{ labels.current_password }}</label><input id="current_password" v-model="passwordForm.current_password" type="password" required :class="inputClass"><p v-if="passwordForm.errors.current_password" :class="errorClass" role="alert">{{ passwordForm.errors.current_password }}</p></div>
                        <div class="space-y-2"><label for="new_password" :class="labelClass">{{ labels.new_password }}</label><input id="new_password" v-model="passwordForm.password" type="password" required :class="inputClass"><p v-if="passwordForm.errors.password" :class="errorClass" role="alert">{{ passwordForm.errors.password }}</p></div>
                        <div class="space-y-2"><label for="password_confirmation" :class="labelClass">{{ labels.confirm_new_password }}</label><input id="password_confirmation" v-model="passwordForm.password_confirmation" type="password" required :class="inputClass"><p v-if="passwordForm.errors.password_confirmation" :class="errorClass" role="alert">{{ passwordForm.errors.password_confirmation }}</p></div>
                        <div class="flex justify-end"><button type="submit" :disabled="passwordForm.processing" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 disabled:opacity-60">{{ labels.change_password }}</button></div>
                    </fieldset>
                </form>
            </section>

            <section v-if="user.is_demo" class="rounded-xl border border-amber-200 bg-amber-50 p-8 dark:border-amber-800 dark:bg-amber-900/30">
                <h2 class="mb-4 text-xl font-semibold text-amber-900 dark:text-amber-200">{{ labels.demo_read_only }}</h2>
                <p class="text-sm text-amber-800 dark:text-amber-300">{{ labels.demo_read_only_description }}</p>
            </section>
            <section v-else class="rounded-xl border border-red-200 bg-red-50 p-8 dark:border-red-800 dark:bg-red-900/30">
                <h2 class="mb-4 text-xl font-semibold text-red-900 dark:text-red-200">{{ labels.delete_account }}</h2>
                <p class="mb-6 text-sm text-red-800 dark:text-red-300">{{ labels.delete_account_warning }}</p>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 active:bg-red-700" @click="showDeleteModal = true">{{ labels.delete_account }}</button>

                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" @keydown.esc.window="showDeleteModal = false">
                    <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>
                        <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                        <div role="dialog" aria-modal="true" aria-labelledby="delete-account-title" class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                            <div class="p-6 sm:p-8">
                                <div class="flex items-start"><div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20"><svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div></div>
                                <div class="mt-4 text-center"><h3 id="delete-account-title" class="text-xl font-semibold text-stone-900 dark:text-white">{{ labels.delete_account }}</h3><div class="mt-3"><p class="text-sm text-stone-600 dark:text-stone-400">{{ labels.delete_account_confirmation }}</p></div></div>
                            </div>
                            <div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8">
                                <button type="button" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto" @click="deleteAccount">{{ labels.delete_account }}</button>
                                <button type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto" @click="showDeleteModal = false">{{ labels.cancel }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
